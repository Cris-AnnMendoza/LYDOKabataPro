# Fix Scholarship Tables in Supabase

## Issue
The scholarship_admin.php page is showing an error because the scholarship tables don't exist in Supabase.

## Solution

### Step 1: Run the SQL Script in Supabase

1. **Open Supabase Dashboard**
   - Go to: https://supabase.com/dashboard
   - Select your project: **wbwiatdjfhviguxzyxue**

2. **Open SQL Editor**
   - Click on "SQL Editor" in the left sidebar
   - Click "+ New Query"

3. **Copy and Run the SQL**
   - Open file: `c:\xampp\htdocs\LYDO\lydo-system\database\verify_and_fix_scholarship.sql`
   - Copy ALL the content
   - Paste into Supabase SQL Editor
   - Click "Run" button (or press F5)

4. **Verify Tables Were Created**
   After running the script, run this verification query:
   ```sql
   SELECT table_name 
   FROM information_schema.tables 
   WHERE table_schema = 'public' AND table_name LIKE 'scholarship%';
   ```
   
   You should see:
   - scholarship_batches
   - scholarship_applications
   - scholarship_documents

### Step 2: Test the Scholarship Page

1. Open your browser
2. Go to: http://localhost/LYDO/lydo-system/admin2/scholarship_admin.php
3. The page should now load without errors

## What the Script Does

✅ Drops old scholarship tables (if they exist with wrong structure)
✅ Creates `scholarship_batches` table (main scholarship programs)
✅ Creates `scholarship_applications` table (student applications)
✅ Creates `scholarship_documents` table (uploaded documents)
✅ Creates indexes for better performance
✅ Creates triggers for automatic `updated_at` timestamps

## Table Structure

### scholarship_batches
- Stores scholarship program batches (e.g., "Iskolar ng Bayan 2026")
- Includes slots, GWA requirements, income limits, exam dates

### scholarship_applications
- Stores individual student applications
- Links to batch_id and user_id
- Tracks status: submitted, under_review, for_exam, approved, rejected, etc.
- Stores exam scores and qualification scores

### scholarship_documents
- Stores uploaded documents for each application
- Document types: application_form, birth_certificate, grades, school_id, income_proof
- Tracks verification status: pending, verified, rejected

## Troubleshooting

**If you get an error about triggers already existing:**
- The error will say: `trigger "xxx" for relation "yyy" already exists`
- This is OK! It means the triggers were created before
- The tables will still be created successfully

**If you get an error about foreign key constraints:**
- Make sure the `youth_users` and `admin_users` tables exist first
- Run the base schema first: `supabase_schema.sql`

**If tables still don't appear:**
1. Check you're in the correct database: `postgres`
2. Check you're in the correct schema: `public`
3. Run the verification query above to see what tables exist
