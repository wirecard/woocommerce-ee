require_relative '.bin/phraseapp/wd-phraseapp.rb'
require_relative '.bin/phraseapp/wd-project.rb'

#-------------------------------------------------------------------------------
# PhraseApp tasks
#-------------------------------------------------------------------------------
namespace :phraseapp do
  desc 'Pull translations'
  task :pull do
    WdPhraseApp.new.pull_locales
  end

  desc 'Parse translatable keys and push to a PhraseApp branch'
  task :push do
    if WdProject.new.has_key_changes?
      WdPhraseApp.new.push_to_branch
    end
  end

  desc '[CI] Pull translations, commit & push to git remote'
  task :ci_update do
    phraseapp = WdPhraseApp.new
    phraseapp.pull_locales
    phraseapp.commit_push_to_repo
  end
end
