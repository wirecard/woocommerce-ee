module Const
  GITHUB_PHRASEAPP_PR_TITLE = '[PhraseApp] Update locales'.freeze
  GITHUB_PHRASEAPP_PR_BODY = 'Update locales from PhraseApp'.freeze
  GIT_PHRASEAPP_COMMIT_MSG = '[skip ci] Update translations from PhraseApp'.freeze
  GIT_PHRASEAPP_BRANCH_BASE = 'master'.freeze
  PHRASEAPP_PROJECT_ID = '9036e89959d471e0c2543431713b7ba1'.freeze
  PHRASEAPP_FALLBACK_LOCALE = 'en_US'.freeze

  # project-specific
  PHRASEAPP_TAG = 'woocommerce'.freeze
  LOCALE_FILE_PREFIX = 'wirecard-woocommerce-extension'.freeze
  LOCALE_SPECIFIC_MAP = {
    'ja_JP': 'ja',
  }.freeze

  # paths relative to project root
  PLUGIN_DIR = 'wirecard-woocommerce-extension'.freeze
  PLUGIN_I18N_DIR = File.join(PLUGIN_DIR, 'languages').freeze
end
