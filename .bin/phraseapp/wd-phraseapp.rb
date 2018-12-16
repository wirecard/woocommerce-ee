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
    @plugin_i18n_dir = File.expand_path(Const::PLUGIN_I18N_DIR, Dir.pwd)
  end

  # Creates a branch on PhraseApp & pushes keys to it.
  def push_to_branch
    create_branch && push_keys
  end

  # Returns true if PhraseApp keys are in sync with the project, false otherwise.
  def is_in_sync?
    pull_pot && WdProject.new.worktree_has_key_changes?
  end

  # Returns an array of locale ids available on the PhraseApp project.
  def get_locale_ids()
    params = OpenStruct.new

    # PhraseApp has a limit of 100 items per page on this paginated endpoint.
    # TODO(nickstamat): handle case of potentially more than 100 locales in total.
    locales = @phraseapp.locales_list(Const::PHRASEAPP_PROJECT_ID, 1, 100, params)
    if locales.last.nil?
      locales = locales.first.map { |l| l.name }
      @log.info('Retrieved list of locales.')
      @log.info(locales)
      return locales
    else
      @log.error('An error occurred while getting locales from PhraseApp.'.red.bright)
      @log.debug(locales.last.errors)
      exit(1)
    end
  end

  # Downloads locale files for all locale ids into the plugin i18n dir.
  def pull_locales()
    @log.info('Downloading locales...'.cyan.bright)
    params = OpenStruct.new({
      :encoding => 'UTF-8',
      :fallback_locale_id => Const::PHRASEAPP_FALLBACK_LOCALE,
      :include_empty_translations => true,
      :include_translated_keys => true,
      :include_unverified_translations => true,
      :tags => Const::PHRASEAPP_TAG,
    })

    get_locale_ids.each do |id|
      @log.info("Downloading locale files for #{id}...".bright)

      file_basename = "#{Const::LOCALE_FILE_PREFIX}-#{Const::LOCALE_SPECIFIC_MAP[id.to_sym] || id}"

      # po/mo
      params.file_format = 'gettext'
      po = @phraseapp.locale_download(Const::PHRASEAPP_PROJECT_ID, id, params)

      params.file_format = 'gettext_mo'
      mo = @phraseapp.locale_download(Const::PHRASEAPP_PROJECT_ID, id, params)

      if po.last.nil? && mo.last.nil?
        File.write(File.join(@plugin_i18n_dir, "#{file_basename}.po"), po)
        File.write(File.join(@plugin_i18n_dir, "#{file_basename}.mo"), mo)
      else
        @log.error("An error occurred while downloading locale #{id} from PhraseApp.".red.bright)
        @log.debug(po.last.nil? ? mo.last.errors : po.last.errors)
        exit(1)
      end
    end

    pull_pot
  end

  # Downloads POT file for fallback locale. Replaces the existing POT in the plugin i18n dir.
  def pull_pot
    @log.info("Downloading POT...".bright)
    params.file_format = 'gettext_template'
    pot = @phraseapp.locale_download(Const::PHRASEAPP_PROJECT_ID, Const::PHRASEAPP_FALLBACK_LOCALE, params)
    if pot.last.nil?
      File.write(File.join(@plugin_i18n_dir, "#{Const::LOCALE_FILE_PREFIX}.pot"), pot)
    else
      @log.error("An error occurred while downloading the POT from PhraseApp.".red.bright)
      @log.debug(pot.last.errors)
      exit(1)
    end
  end

  # Returns branch name for use on PhraseApp. Uses normalized local git branch, prepended by the plugin tag.
  def branch_name
    local_branch_name = Git.open(Dir.pwd, :log => @log).current_branch
    "#{Const::PHRASEAPP_TAG}-#{local_branch_name.downcase.gsub(/(\W|_)/, '-')}"
  end

  # Creates branch on PhraseApp for the current git branch.
  def create_branch
    if HighLine.agree("This will create branch '#{branch_name}' on PhraseApp. Proceed? (y/n)".bright)
      params = OpenStruct.new({ :name => branch_name })

      begin
        @phraseapp.branch_create(Const::PHRASEAPP_PROJECT_ID, params)
        @log.info('Success! Branch created.'.green.bright)
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

    File.rename(pot_new_path, pot_path)

    params = OpenStruct.new({
      :autotranslate => false,
      :branch => branch_name,
      :file => pot_path,
      :file_encoding => 'UTF-8',
      :file_format => 'gettext_template',
      :locale_id => Const::PHRASEAPP_FALLBACK_LOCALE,
      :tags => Const::PHRASEAPP_TAG,
      :update_descriptions => false,
      :update_translations => true,
    })

    upload = @phraseapp.upload_create(Const::PHRASEAPP_PROJECT_ID, params)
    if upload.last.nil?
      @log.info('Success! Uploaded to PhraseApp'.green.bright)
    else
      @log.error('An error occurred while uploading to PhraseApp.'.red.bright)
      @log.debug(upload.last.errors)
    end
  end
end
