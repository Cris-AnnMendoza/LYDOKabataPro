@echo off
cd /d C:\xampp\htdocs\LYDO

echo Adding all files including .gitignore...
"C:\Program Files\Git\bin\git.exe" add .

echo Committing security fix...
"C:\Program Files\Git\bin\git.exe" commit -m "Security: Removed API keys from config, added .gitignore"

echo Pushing to GitHub...
"C:\Program Files\Git\bin\git.exe" push https://Cris-AnnMendoza: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@github.com/Cris-AnnMendoza/LYDOKabataPro.git main --force

echo.
echo ========================================
echo PUSH COMPLETE!
echo ========================================
echo.
echo IMPORTANT: Revoke token at https://github.com/settings/tokens
pause
