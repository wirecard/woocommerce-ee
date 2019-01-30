# frozen_string_literal: true

module Const
  GITHUB_PHRASEAPP_PR_TITLE = '[PhraseApp] Update locales'
  GITHUB_PHRASEAPP_PR_BODY = 'Update locales from PhraseApp'
  GIT_PHRASEAPP_COMMIT_MSG = '[skip ci] Update translations from PhraseApp'
  GIT_PHRASEAPP_BRANCH_BASE = 'master'
  PHRASEAPP_PROJECT_ID = '9036e89959d471e0c2543431713b7ba1'
  PHRASEAPP_FALLBACK_LOCALE = 'en_US'

  # project-specific
  PHRASEAPP_TAG = 'woocommerce'
  LOCALE_FILE_PREFIX = 'wirecard-woocommerce-extension'
  LOCALE_SPECIFIC_MAP = {
    'ja_JP': 'ja',
  }.freeze

  # paths relative to project root
  PLUGIN_DIR = 'wirecard-woocommerce-extension'
  PLUGIN_I18N_DIR = File.join(PLUGIN_DIR, 'languages')
end
