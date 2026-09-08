=====================================
GRADLE JVM ISSUE - SOLUTION
=====================================

PROBLEM:
Your Flutter app can't build because Gradle is using Java 8 instead of Java 17.

ERROR MESSAGE:
"Gradle requires JVM 17 or later to run. Your build is currently configured to use JVM 8."

=====================================
SOLUTION - FOLLOW THESE STEPS:
=====================================

STEP 1: Run the Fix Script
---------------------------
Open Command Prompt or PowerShell in this folder and run:

    fix_gradle.bat

OR if using PowerShell:

    .\fix_gradle.ps1


STEP 2: Try Running Your App
-----------------------------
After the script completes, run:

    flutter run


=====================================
IF THE SCRIPT DOESN'T WORK:
=====================================

Run these commands manually:

1. Stop Gradle daemon:
   cd android
   gradlew --stop
   cd ..

2. Clean Flutter:
   flutter clean

3. Get dependencies:
   flutter pub get

4. Run the app:
   flutter run


=====================================
STILL NOT WORKING?
=====================================

Try this nuclear option:

1. Close ALL terminals and VS Code
2. Delete these folders manually:
   - lydo_mobile\build
   - lydo_mobile\android\build
   - lydo_mobile\android\app\build
   - lydo_mobile\.dart_tool

3. Open a NEW terminal
4. Run:
   cd c:\xampp\htdocs\LYDO\lydo_mobile
   flutter clean
   flutter pub get
   flutter run


=====================================
WHAT WAS FIXED:
=====================================

✓ Configured Gradle to use JDK 17 (located at C:\Program Files\Java\jdk-17)
✓ Updated android/gradle.properties
✓ Updated android/local.properties
✓ Reduced memory allocation to prevent OOM errors


=====================================
VERIFICATION:
=====================================

To check if Java 17 is being used:

    cd android
    gradlew --version

Look for "JVM: 17" in the output.


=====================================
SUPPORT:
=====================================

If you still encounter issues, make sure:
1. JDK 17 is installed at: C:\Program Files\Java\jdk-17
2. You have internet connection (Gradle downloads dependencies)
3. You have sufficient disk space (at least 2GB free)

=====================================
