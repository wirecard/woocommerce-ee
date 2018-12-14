#!/usr/bin/env ruby

require 'logger'
require_relative '.bin/phraseapp/env.rb'
require_relative '.bin/phraseapp/wd-phraseapp.rb'

$log = Logger.new(STDOUT, level: Env::DEBUG ? 'DEBUG' : 'INFO')

def main
  if Env::PHRASEAPP_PULL == '1'
    pa = WdPhraseApp.new
    pa.update_translations
  end
end

main
