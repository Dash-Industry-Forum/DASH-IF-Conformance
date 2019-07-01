#!/bin/bash
input="./list.txt"
allowedmodules=("TestSubmoduleNeeded") # List the modules allowed to be pushed

cd ../..
git config --file .gitmodules --name-only --get-regexp path > list.txt

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
    
    git config submodule.${sub}.active false # Ignore all the submodules in the main repository
  fi
done < "$input" 

git push downstream master # Finally push the main repository