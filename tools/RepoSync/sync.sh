#!/bin/bash
input="./list.txt"


### Github Part
allowedmodules=("TestSubmoduleNeeded")

cd ../..
git config --file .gitmodules --name-only --get-regexp path > list.txt
# Update the submodules
while IFS= read -r line
do
  if [[ $line == *"submodule"* ]]; then
    sub="$(cut -d'.' -f2 <<<$line)"
    if [[ " ${allowedmodules[*]} " == *" ${sub} "* ]]; then
      cd "$sub"
      echo "Entered the directory $sub"
      git push downstream master # In all submodules there is a remote "downstream" pointing to the Gitlab repository
      cd ..
      echo "Returning from the directory $sub"
    fi
  fi
done < "$input" 

# Remove the submodules and push the main repo (sync branch) to the desired main repo (sync branch)
git pull origin master
git checkout -b sync
while IFS= read -r line
do
  if [[ $line == *"submodule"* ]]; then
    sub="$(cut -d'.' -f2 <<<$line)"
    git submodule deinit -f $sub
    rm -rf .git/modules/${sub}
    git rm -f $sub
  fi
done < "$input"
#git rm .gitmodules
git commit -m "Removed the submodules"
git push downstream sync 

# Finally bring the main repo to master
git checkout master
git branch -D sync
while IFS= read -r line
do
  if [[ $line == *"submodule"* ]]; then
    sub="$(cut -d'.' -f2 <<<$line)"
    git submodule init $sub
  fi
done < "$input"
git submodule update

while IFS= read -r line
do
  if [[ $line == *"submodule"* ]]; then
    sub="$(cut -d'.' -f2 <<<$line)"
    cd "$sub"
    git checkout master
    cd ..
  fi
done < "$input"
###


### GitLab Part
# Update the desired main repo pointing to the latest submodules
desiredlocal="/var/www/html/TestGitSync/Gitlab/Test/"
cd $desiredlocal
git config --file .gitmodules --name-only --get-regexp path > list.txt
while IFS= read -r line
do
  if [[ $line == *"submodule"* ]]; then
    sub="$(cut -d'.' -f2 <<<$line)"
    cd "$sub"
    git pull origin master
    cd ..
  fi
done < "$input" 
git add .
git commit -m "Point to the latest submodules"
git push origin master
###