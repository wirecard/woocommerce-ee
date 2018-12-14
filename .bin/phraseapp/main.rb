#!/usr/bin/env ruby

require 'logger'
require_relative 'env.rb'
require_relative 'wd-phraseapp.rb'

$log = Logger.new(STDOUT, level: Env::DEBUG ? 'DEBUG' : 'INFO')

def main
  if Env::PHRASEAPP_PULL == '1'
    pa = WdPhraseApp.new
    pa.update_translations
  end
end

main
