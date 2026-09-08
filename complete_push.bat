@echo off
echo Completing the merge and pushing...
cd /d C:\xampp\htdocs\LYDO

echo Adding all files...
"C:\Program Files\Git\bin\git.exe" add .

echo Committing merge...
"C:\Program Files\Git\bin\git.exe" commit -m "Merged and updated LYDO system with dashboard improvements"

echo Force pushing to GitHub...
"C:\Program Files\Git\bin\git.exe" push https://Cris-AnnMendoza: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@github.com/Cris-AnnMendoza/LYDOKabataPro.git main --force

echo.
echo ===============================================
echo DONE! All files are now pushed to GitHub!
echo ===============================================
pause
