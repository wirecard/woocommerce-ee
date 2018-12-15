begin
  require 'bundler/setup'
rescue LoadError => e
  warn 'We require bundler. Please install it with `gem install bundler`.'
  warn 'Also, make sure you run rake as `.bin/rake`\n'
  warn '\n\n'
  warn e.message
  warn e.backtrace.join('\n')
  exit(1)
end

require_relative '.bin/phraseapp/wd-phraseapp.rb'

#-------------------------------------------------------------------------------
# PhraseApp tasks for Dev
#-------------------------------------------------------------------------------
namespace :phraseapp do
  desc 'Pull translations'
  task :pull do
    phraseapp = WdPhraseApp.new
    phraseapp.pull_locales
  end

  # desc 'Parse translatable keys and push to a PhraseApp branch'
  # task :push do
  #   phraseapp = WdPhraseApp.new
  #   phraseapp.create_branch_from_current
  #   project.generate_pot
  #   phraseapp.push_pot
  # end

  desc '[CI] Pull translations, commit & push to remote branch'
  task :ci_update do
    phraseapp = WdPhraseApp.new
    phraseapp.pull_locales
    phraseapp.commit_push_translations
  end
end
