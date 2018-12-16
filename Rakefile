require_relative '.bin/phraseapp/wd-phraseapp.rb'
require_relative '.bin/phraseapp/wd-project.rb'

#-------------------------------------------------------------------------------
# PhraseApp tasks
#-------------------------------------------------------------------------------
namespace :phraseapp do
  desc 'Pull locale files'
  task :pull do
    WdPhraseApp.new.pull_locales
  end

  desc 'Parse translatable keys and push to a PhraseApp branch'
  task :push do
    if WdProject.new.worktree_has_key_changes?
      WdPhraseApp.new.push_to_branch
    end
  end

  desc '[CI] Pull locales, commit & push to git remote'
  task :ci_update do
    WdPhraseApp.new.pull_locales && WdProject.new.commit_push_pr_locales
  end
end
