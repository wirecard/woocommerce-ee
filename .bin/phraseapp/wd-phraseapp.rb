require 'git'
require 'highline'
require 'logger'
require 'phraseapp-ruby'
require 'rainbow/refinement'
require_relative 'const.rb'
require_relative 'env.rb'
require_relative 'wd-github.rb'
require_relative 'wd-project.rb'

using Rainbow

class WdPhraseApp
  def initialize()
    @log = Logger.new(STDOUT, level: Env::DEBUG ? 'DEBUG' : 'INFO')

    credentials = PhraseApp::Auth::Credentials.new(token: Env::PHRASEAPP_TOKEN, debug: Env::DEBUG)
    @phraseapp = PhraseApp::Client.new(credentials)
    @plugin_i18n_dir = File.expand_path(Const::PLUGIN_I18N_DIR, Dir.pwd)
  end

  def push_to_branch
    create_branch && push_keys
  end

  def commit_push_to_repo()
    @log.info('Committing & pushing any added/changed locales...')
    git = Git.open(Dir.pwd, :log => @log)
    git.add(File.join(@plugin_i18n_dir, '*.po'))
    git.add(File.join(@plugin_i18n_dir, '*.mo'))
    git.add(File.join(@plugin_i18n_dir, '*.pot'))
    git.commit('[skip ci] Update translations from PhraseApp')
    git.push(
      "https://#{Env::GITHUB_TOKEN}@github.com/#{Env::TRAVIS_REPO_SLUG}",
      "HEAD:refs/heads/#{Env::TRAVIS_BRANCH}"
    )
    WdGithub.new.create_pr(Env::TRAVIS_REPO_SLUG, 'master', Env::TRAVIS_BRANCH, Const::GITHUB_PHRASEAPP_PR_TITLE, '')
  rescue Git::GitExecuteError => e
    @log.warn(e)
  end

  def get_locale_ids()
    params = OpenStruct.new

    # PhraseApp has a limit of 100 items per page on this paginated endpoint
    # TODO: handle case of potentially more than 100 locales in total
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
      @log.info("Downloading locale file for #{id}...".bright)

      file_basename = "#{Const::LOCALE_FILE_PREFIX}-#{Const::LOCALE_SPECIFIC_MAP[id.to_sym] || id}"

      # po
      params.file_format = 'gettext'
      File.write(
        File.join(@plugin_i18n_dir, "#{file_basename}.po"),
        @phraseapp.locale_download(Const::PHRASEAPP_PROJECT_ID, id, params)
      ) || (@log.error("Couldn't write file #{@plugin_i18n_dir}/#{file_basename}.po") && exit(1))

      # mo
      params.file_format = 'gettext_mo'
      File.write(
        File.join(@plugin_i18n_dir, "#{file_basename}.mo"),
        @phraseapp.locale_download(Const::PHRASEAPP_PROJECT_ID, id, params)
      ) || (@log.error("Couldn't write file #{@plugin_i18n_dir}/#{file_basename}.mo") && exit(1))
    end

    # pot of the fallback locale
    params.file_format = 'gettext_template'
    File.write(
      File.join(@plugin_i18n_dir, "#{Const::LOCALE_FILE_PREFIX}.pot"),
      @phraseapp.locale_download(Const::PHRASEAPP_PROJECT_ID, Const::PHRASEAPP_FALLBACK_LOCALE, params)
    ) || (@log.error("Couldn't write file #{@plugin_i18n_dir}/#{Const::LOCALE_FILE_PREFIX}.pot") && exit(1))
  end

  def branch_name
    local_branch_name = Git.open(Dir.pwd, :log => @log).current_branch
    "#{Const::PHRASEAPP_TAG}-#{local_branch_name.downcase.gsub(/(\W|_)/, '-')}"
  end

  def create_branch
    if HighLine.agree("This will create branch '#{branch_name}' on PhraseApp. Proceed? (y/n)".bright)
      params = OpenStruct.new
      params.name = branch_name

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

  def push_keys
    project = WdProject.new
    new_pot_path = project.new_pot_path
    pot_path = project.pot_path

    if !File.exist?(new_pot_path) || !File.exist?(pot_path)
      @log.fatal('Couldn\'t find the POT files.'.red.bright) && exit(1)
    end

    File.rename(new_pot_path, pot_path)

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
