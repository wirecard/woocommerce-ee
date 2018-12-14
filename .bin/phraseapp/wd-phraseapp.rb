require 'git'
require 'phraseapp-ruby'
require_relative 'const.rb'
require_relative 'env.rb'
require_relative 'wd-github.rb'

class WdPhraseApp
  def initialize()
    credentials = PhraseApp::Auth::Credentials.new(token: Env::PHRASEAPP_TOKEN)
    @phraseapp = PhraseApp::Client.new(credentials)
    @git = Git.open(Dir.pwd, :log => $log)
    @github = WdGithub.new
    @plugin_i18n_dir = File.expand_path(Const::PLUGIN_I18N_DIR, Dir.pwd)
  end

  def update_translations()
    $log.info('Updating translations...')
    pull_locales

    changed = @git.status.changed.any? { |key, val| key =~ /#{Const::PLUGIN_I18N_DIR}.*(?:po|mo)/ }
    untracked = @git.status.untracked.any? { |key, val| key =~ /#{Const::PLUGIN_I18N_DIR}.*(?:po|mo)/ }

    if changed || untracked
      $log.info('Adding and pushing changed/untracked files...')

      $log.debug('Changed:')
      $log.debug(@git.status.changed.keys)
      $log.debug('Untracked:')
      $log.debug(@git.status.untracked.keys)

      @git.add(File.join(@plugin_i18n_dir, '*.po'))
      @git.add(File.join(@plugin_i18n_dir, '*.mo'))
      @git.commit('[skip ci] Update translations from PhraseApp')
      @git.push(
        "https://#{Env::GITHUB_TOKEN}@github.com/#{Env::TRAVIS_REPO_SLUG}",
        "HEAD:refs/heads/#{Env::TRAVIS_BRANCH}"
      )
      @github.create_pr(Env::TRAVIS_REPO_SLUG, 'master', Env::TRAVIS_BRANCH, Const::GITHUB_PHRASEAPP_PR_TITLE, '')
    end
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
    params = OpenStruct.new
    params.encoding = 'UTF-8'
    params.fallback_locale_id = Const::PHRASEAPP_FALLBACK_LOCALE
    params.include_empty_translations = true
    params.include_translated_keys = true
    params.include_unverified_translations = true
    params.tags = Const::PHRASEAPP_TAGS

    get_locale_ids.each do |id|
      file_basename = "#{Const::LOCALE_FILE_PREFIX}#{Const::LOCALE_SPECIFIC_MAP[id.to_sym] || id}"

      params.file_format = 'gettext'
      File.write(
        File.join(@plugin_i18n_dir, "#{file_basename}.po"),
        @phraseapp.locale_download(Const::PHRASEAPP_PROJECT_ID, id, params)
      ) || ($log.error("Couldn't write file #{@plugin_i18n_dir}/#{file_basename}.po") && exit(1))

      params.file_format = 'gettext_mo'
      File.write(
        File.join(@plugin_i18n_dir, "#{file_basename}.mo"),
        @phraseapp.locale_download(Const::PHRASEAPP_PROJECT_ID, id, params)
      ) || ($log.error("Couldn't write file #{@plugin_i18n_dir}/#{file_basename}.mo") && exit(1))
    end
  end
end
