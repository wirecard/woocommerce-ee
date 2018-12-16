require 'open3'
require 'rainbow/refinement'
require 'simple_po_parser'
require_relative 'const.rb'

using Rainbow

# Project-specific helpers
class WdProject
  attr_reader :pot_path
  attr_reader :new_pot_path

  def initialize
    @log = Logger.new(STDOUT, level: Env::DEBUG ? 'DEBUG' : 'INFO')
    @pot_path = File.join(Const::PLUGIN_I18N_DIR, Const::LOCALE_FILE_PREFIX + '.pot')
    @new_pot_path = @pot_path + '.new'
  end

  def has_key_changes?
    has_key_changes = generate_new_pot && is_new_pot_different?
    unless has_key_changes
      cleanup_pot
    end

    has_key_changes
  end

  def generate_new_pot
    @log.info('curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar')
    stdout, stderr, status = Open3.capture3('curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar')
    @log.debug(stdout)
    if status != 0
      @log.error(stderr)
      exit(1)
    end

    @log.info("php wp-cli.phar i18n make-pot #{Const::PLUGIN_DIR} #{@new_pot_path}")
    stdout, stderr, status = Open3.capture3("php wp-cli.phar i18n make-pot #{Const::PLUGIN_DIR} #{@new_pot_path}")
    @log.info(stdout)
    if status != 0
      @log.error(stderr)
      exit(1)
    end

    true
  end

  def cleanup_pot
    File.delete(new_pot_path) || true
  end

  def is_new_pot_different?
    existing_keys = SimplePoParser.parse(@pot_path).map { |h| h[:msgid] }.select { |k| !k.empty? }
    new_keys = SimplePoParser.parse(@new_pot_path).map { |h| h[:msgid] }.select { |k| !k.empty? }

    @log.info("Number of keys in the existing POT: #{existing_keys.size}")
    @log.info("Number of keys in the new POT: #{new_keys.size}")

    @log.info("Removed keys: #{existing_keys - new_keys}")
    @log.info("Added keys: #{new_keys - existing_keys}")

    # keys are unique; we use the intersection to detect differences
    has_differences = (new_keys.size != existing_keys.size) || (new_keys & existing_keys != new_keys)
    if has_differences
      @log.warn('Changes to translatable keys have been detected in your working tree.'.yellow.bright)
    else
      @log.info('No changes to translatable keys have been detected in your working tree.'.green.bright)
    end

    has_differences
  end
end
