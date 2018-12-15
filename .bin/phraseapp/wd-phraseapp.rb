require 'git'
require 'highline'
require 'logger'
require 'phraseapp-ruby'
require_relative 'const.rb'
require_relative 'env.rb'
require_relative 'wd-github.rb'

class WdPhraseApp
  def initialize()
    @log = Logger.new(STDOUT, level: Env::DEBUG ? 'DEBUG' : 'INFO')

    credentials = PhraseApp::Auth::Credentials.new(token: Env::PHRASEAPP_TOKEN)
    @phraseapp = PhraseApp::Client.new(credentials)
    @plugin_i18n_dir = File.expand_path(Const::PLUGIN_I18N_DIR, Dir.pwd)
  end

  def commit_push_translations()
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
    github = WdGithub.new
    github.create_pr(Env::TRAVIS_REPO_SLUG, 'master', Env::TRAVIS_BRANCH, Const::GITHUB_PHRASEAPP_PR_TITLE, '')
  rescue Git::GitExecuteError => e
    @log.warn(e)
  end

  def get_locale_ids()
    params = OpenStruct.new

    # TODO: handle case of potentially more than 100 locales
    @phraseapp.locales_list(Const::PHRASEAPP_PROJECT_ID, 1, 100, params).
      select { |l| !l.nil? }.
      map { |l| Array(l) }.
      flatten!.
      map { |l| l.name }
  end

  def pull_locales()
    @log.info('Downloading locales...')
    params = OpenStruct.new
    params.encoding = 'UTF-8'
    params.fallback_locale_id = Const::PHRASEAPP_FALLBACK_LOCALE
    params.include_empty_translations = true
    params.include_translated_keys = true
    params.include_unverified_translations = true
    params.tags = Const::PHRASEAPP_TAG

    get_locale_ids.each do |id|
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
end
