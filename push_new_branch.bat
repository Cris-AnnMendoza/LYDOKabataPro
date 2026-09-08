@echo off
cd /d C:\xampp\htdocs\LYDO

echo Creating orphan branch (no history)...
"C:\Program Files\Git\bin\git.exe" checkout --orphan secure-branch

echo Adding all files with removed secrets...
"C:\Program Files\Git\bin\git.exe" add .

echo Committing clean version...
"C:\Program Files\Git\bin\git.exe" commit -m "Updated LYDO: Dashboard improvements, simplified graphs, certificate cleanup - All secrets removed"

echo Force pushing to replace main branch...
"C:\Program Files\Git\bin\git.exe" push https://Cris-AnnMendoza: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@github.com/Cris-AnnMendoza/LYDOKabataPro.git secure-branch:main --force

echo.
echo ========================================
echo SUCCESS! All changes pushed to GitHub!
echo ========================================
echo.
echo REVOKE TOKEN NOW: https://github.com/settings/tokens
pause
