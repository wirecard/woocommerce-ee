require 'logger'
require 'open3'
require 'rainbow/refinement'
require 'simple_po_parser'
require_relative 'const.rb'
require_relative 'env.rb'
require_relative 'wd-git.rb'
require_relative 'wd-github.rb'

using Rainbow

# Project-specific helpers
class WdProject
  attr_reader :pot_path
  attr_reader :pot_new_path

  def initialize
    @log = Logger.new(STDOUT, level: Env::DEBUG ? 'DEBUG' : 'INFO')
    @pot_path = File.join(Const::PLUGIN_I18N_DIR, Const::LOCALE_FILE_PREFIX + '.pot')
    @pot_new_path = @pot_path + '.new'
    @repo = Env::TRAVIS_REPO_SLUG
    @head = Env::TRAVIS_BRANCH
  end

  def worktree_has_key_changes?
    pot_generate && has_key_changes?
  end

  def pot_generate
    @log.info('curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar')
    stdout, stderr, status = Open3.capture3('curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar')
    @log.debug(stdout)
    if status != 0
      @log.error(stderr)
      exit(1)
    end

    @log.info("php wp-cli.phar i18n make-pot #{Const::PLUGIN_DIR} #{@pot_new_path}")
    stdout, stderr, status = Open3.capture3("php wp-cli.phar i18n make-pot #{Const::PLUGIN_DIR} #{@pot_new_path}")
    @log.info(stdout)
    if status != 0
      @log.error(stderr)
      exit(1)
    end

    true
  end

  def has_key_changes?
    existing_keys = SimplePoParser.parse(@pot_path).map { |h| h[:msgid] }.select { |k| !k.empty? }
    new_keys = SimplePoParser.parse(@pot_new_path).map { |h| h[:msgid] }.select { |k| !k.empty? }

    @log.info("Number of keys in the existing POT: #{existing_keys.size}")
    @log.info("Number of keys in the new POT: #{new_keys.size}")

    @log.info("Removed keys: #{existing_keys - new_keys}")
    @log.info("Added keys: #{new_keys - existing_keys}")

    # keys are unique; we use the intersection to detect differences
    has_key_changes = (new_keys.size != existing_keys.size) || (new_keys & existing_keys != new_keys)
    if has_key_changes
      @log.warn('Changes to translatable keys have been detected in your working tree.'.yellow.bright)
    else
      @log.info('No changes to translatable keys have been detected in your working tree.'.green.bright)
    end

    has_key_changes
  end

  def commit_push_pr_locales()
    path = Const::PLUGIN_I18N_DIR
    base = Const::GIT_PHRASEAPP_BRANCH_BASE
    commit_msg = Const::GIT_PHRASEAPP_COMMIT_MSG
    pr_title = Const::GITHUB_PHRASEAPP_PR_TITLE
    pr_body = Const::GITHUB_PHRASEAPP_PR_BODY

    WdGit.new.commit_push(@repo, @head, path, commit_msg)
    WdGithub.new.create_pr(@repo, base, @head, pr_title, pr_body)
  end
end
