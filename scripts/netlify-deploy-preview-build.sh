#!/usr/bin/env sh
set -eu

if git diff --quiet HEAD^ HEAD -- docs mkdocs.yml requirements.txt; then
  mkdir -p site
  cat > site/index.html <<'HTML'
<!doctype html>
<html lang="en">
<meta charset="utf-8">
<title>SASO Deploy Preview</title>
<body>No documentation changes in this deploy preview.</body>
</html>
HTML
  exit 0
fi

pip install --upgrade pip
pip install -r requirements.txt
mkdocs build --strict
