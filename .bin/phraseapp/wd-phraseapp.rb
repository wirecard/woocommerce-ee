require 'git'
require 'highline'
require 'logger'
require 'phraseapp-ruby'
require 'rainbow/refinement'
require_relative 'const.rb'
require_relative 'env.rb'
require_relative 'wd-git.rb'
require_relative 'wd-github.rb'
require_relative 'wd-project.rb'

using Rainbow

# Methods to handle PhraseApp locales
class WdPhraseApp
  def initialize()
    @log = Logger.new(STDOUT, level: Env::DEBUG ? 'DEBUG' : 'INFO')
    credentials = PhraseApp::Auth::Credentials.new(token: Env::PHRASEAPP_TOKEN, debug: Env::DEBUG)
    @phraseapp = PhraseApp::Client.new(credentials)

    @phraseapp_id              = Const::PHRASEAPP_PROJECT_ID
    @phraseapp_fallback_locale = Const::PHRASEAPP_FALLBACK_LOCALE
    @phraseapp_tag             = Const::PHRASEAPP_TAG
    @locale_file_prefix        = Const::LOCALE_FILE_PREFIX
    @locale_specific_map       = Const::LOCALE_SPECIFIC_MAP
    @plugin_i18n_dir           = File.expand_path(Const::PLUGIN_I18N_DIR, Dir.pwd)
  end

  # Creates a branch on PhraseApp & pushes keys to it.
  def push_to_branch
    create_branch && push_keys
  end

  # Returns true if PhraseApp keys are in sync with the project, false otherwise.
  def is_in_sync?
    pull_pot && !WdProject.new.worktree_has_key_changes?
  end

  # Returns an array of locale ids available on the PhraseApp project.
  def get_locale_ids()
    # PhraseApp has a limit of 100 items per page on this paginated endpoint.
    locales, err = @phraseapp.locales_list(@phraseapp_id, 1, 100, OpenStruct.new)
    if err.nil?
      locales = locales.map { |l| l.name }
      @log.info('Retrieved list of locales.')
      @log.info(locales)
      return locales
    else
      @log.error('An error occurred while getting locales from PhraseApp.'.red.bright)
      @log.debug(err)
      exit(1)
    end
  end

  # Downloads locale files for all locale ids into the plugin i18n dir.
  def pull_locales()
    @log.info('Downloading locales...'.cyan.bright)
    params = OpenStruct.new({
      :encoding => 'UTF-8',
      :fallback_locale_id => @phraseapp_fallback_locale,
      :include_empty_translations => true,
      :include_translated_keys => true,
      :include_unverified_translations => true,
      :tags => @phraseapp_tag,
    })

    get_locale_ids.each do |id|
      @log.info("Downloading locale files for #{id}...".bright)

      file_basename = "#{@locale_file_prefix}-#{@locale_specific_map[id.to_sym] || id}"

      # po/mo
      params.file_format = 'gettext'
      po, err = @phraseapp.locale_download(@phraseapp_id, id, params)
      if err.nil?
        # Look into each translation string. If one contains a line break at the end remove it.
        # Needed to generate mo files from po files without errors
        po.gsub!(/(?<translation>msgstr .*)\\n\"/, '\k<translation>"')
        File.write(File.join(@plugin_i18n_dir, "#{file_basename}.po"), po)
      else
        @log.error("An error occurred while downloading locale #{id}.po from PhraseApp.".red.bright)
        @log.debug(err)
        exit(1)
      end

      params.file_format = 'gettext_mo'
      mo, err = @phraseapp.locale_download(@phraseapp_id, id, params)
      if err.nil?
        File.write(File.join(@plugin_i18n_dir, "#{file_basename}.mo"), mo)
      else
        @log.error("An error occurred while downloading locale #{id}.mo from PhraseApp.".red.bright)
        @log.debug(err)
        exit(1)
      end
    end

    pull_pot
  end

  # Downloads POT file for fallback locale. Replaces the existing POT in the plugin i18n dir.
  def pull_pot
    @log.info("Downloading POT...".bright)

    pot, err = @phraseapp.locale_download(@phraseapp_id, @phraseapp_fallback_locale, OpenStruct.new({
      :encoding => 'UTF-8',
      :fallback_locale_id => @phraseapp_fallback_locale,
      :file_format => 'gettext_template',
      :include_empty_translations => true,
      :include_translated_keys => true,
      :include_unverified_translations => true,
      :tags => @phraseapp_tag,
    }))

    if err.nil?
      File.write(File.join(@plugin_i18n_dir, "#{@locale_file_prefix}.pot"), pot)
    else
      @log.error("An error occurred while downloading the POT from PhraseApp.".red.bright)
      @log.debug(err)
      exit(1)
    end
  end

  # Returns branch name for use on PhraseApp. Uses normalized local git branch, prepended by the plugin tag.
  def branch_name
    local_branch_name = Git.open(Dir.pwd, :log => @log).current_branch
    "#{@phraseapp_tag}-#{local_branch_name.downcase.gsub(/(\W|_)/, '-')}"
  end

  # Creates branch on PhraseApp for the current git branch.
  def create_branch
    if HighLine.agree("This will create branch '#{branch_name}' on PhraseApp. Proceed? (y/n)".bright)
      begin
        _branch, err = @phraseapp.branch_create(@phraseapp_id, OpenStruct.new({ :name => branch_name }))
        if err.nil?
          @log.info('Success! Branch created.'.green.bright)
        else
          @log.error("An error occurred while creating branch on PhraseApp.".red.bright)
          @log.debug(err)
          exit(1)
        end
      rescue NoMethodError => e
        @log.warn('Request failed. Branch already exists.'.cyan.bright)
        @log.debug(e)
      end
      true
    else
      @log.info('Aborted.')
      false
    end
  end

  # Uploads previously generated POT file to the PhraseApp branch.
  def push_keys
    project = WdProject.new
    pot_new_path = project.pot_new_path
    pot_path = project.pot_path

    if !File.exist?(pot_new_path) || !File.exist?(pot_path)
      @log.fatal('Couldn\'t find the POT files.'.red.bright) && exit(1)
    end

    begin
      File.rename(pot_new_path, pot_path)
    rescue => e
      @log.error("Error while renaming file #{pot_new_path} to #{pot_path}.")
      @log.debug(e.inspect)
      exit(1)
    end

    upload, err = @phraseapp.upload_create(@phraseapp_id, OpenStruct.new({
      :autotranslate => false,
      :branch => branch_name,
      :file => pot_path,
      :file_encoding => 'UTF-8',
      :file_format => 'gettext_template',
      :locale_id => @phraseapp_fallback_locale,
      :tags => @phraseapp_tag,
      :update_descriptions => false,
      :update_translations => true,
    }))

    if err.nil?
      @log.info('Success! Uploaded to PhraseApp'.green.bright)
      @log.info(upload.summary)
    else
      @log.error('An error occurred while uploading to PhraseApp.'.red.bright)
      @log.debug(err)
      exit(1)
    end
  end
end
