# Quiz Expiration Feature - Documentation

## Overview
This feature automatically manages quiz expiration, marking non-attending students, and cleaning up expired quizzes from the system.

## Features Implemented

### 1. **Quiz Due Date and Time Selection**
- **File**: `add_question.php` (Step 3: Publish)
- Teachers can set a due date and time when publishing a quiz
- Due date is stored in three formats:
  - `due_date`: YYYY-MM-DD format (e.g., "2025-03-15")
  - `due_time`: HH:MM format (e.g., "14:30")
  - `due_datetime`: Full datetime (e.g., "2025-03-15 14:30:00")

### 2. **Student Dashboard - Quiz Filtering**
- **File**: `student_dashboard.php`
- Only shows quizzes that haven't expired yet
- Displays countdown timers showing:
  - Days remaining
  - Hours remaining
  - Due date and time
- Non-expired quizzes are shown in "Available Quizzes" section

### 3. **Quiz Access Control**
- **File**: `quiz.php`
- Students cannot access expired quizzes
- If a student tries to access an expired quiz, they see:
  - Error message: "⏰ Quiz Expired"
  - The quiz's due date and time
  - A link to return to quiz selection

### 4. **Automatic Cleanup and Non-Attendance Marking**
- **File**: `cleanup_expired_quizzes.php`
- Finds all expired quizzes (due_datetime < current time)
- For each expired quiz:
  - Marks all non-attending students with `status: 'not_attended'`
  - Creates result records with score = 0
  - Deletes the quiz and all its questions
- Can be run manually or set up as a scheduled task

### 5. **Teacher Dashboard - Expiration Monitoring**
- **File**: `teacher_dashboard.php`
- Shows "Published Quizzes" section with:
  - Quiz title
  - Number of questions
  - Due date (formatted)
  - Status (Active/Expired)
  - Days remaining (counted down from due date)
- "Run Cleanup Now" button to manually trigger cleanup

### 6. **Student Results Display**
- **File**: `student_dashboard.php`
- Results table now shows:
  - Quiz title
  - Score and percentage
  - **Status badge**: 
    - "COMPLETED" (blue) - for quizzes the student took
    - "NOT ATTENDED" (red) - for quizzes they didn't take
  - Date of attempt/non-attendance
- Non-attended quiz rows are visually distinguished (grayed out)

## Database Schema

### Quizzes Collection Fields
```
{
  "_id": ObjectId,
  "quiz_title": "String",
  "college": "String",
  "status": "published",
  "due_date": "YYYY-MM-DD",
  "due_time": "HH:MM",
  "due_datetime": "YYYY-MM-DD HH:MM:SS",
  "question_count": Number,
  "time_limit_minutes": Number,
  ...
}
```

### Results Collection Fields
```
{
  "_id": ObjectId,
  "username": "String",
  "quiz_title": "String",
  "college": "String",
  "score": Number,
  "total": Number,
  "status": "completed" OR "not_attended",
  "date": "YYYY-MM-DD H:i:s",
  ...
}
```

## Usage Workflow

### For Teachers:
1. **Create and Publish Quiz**
   - Go to Add Question page
   - Step 1: Select or create quiz title
   - Step 2: Add all questions
   - Step 3: Set due date, due time, and time limit
   - Publish the quiz

2. **Monitor Quiz Expiration**
   - View "Published Quizzes" section in Teacher Dashboard
   - See countdown of days/hours remaining
   - May optionally click "Run Cleanup Now" before auto-scheduled cleanup

### For Students:
1. **View Available Quizzes**
   - Go to Student Dashboard
   - See "Available Quizzes" section
   - View countdown timer for each quiz
   - Click "Start Quiz" to begin

2. **Access Expired Quiz**
   - Cannot start expired quizzes
   - See error message with expiration time
   - Can view results in "My Past Results"
   - Non-attended entries shown with "NOT ATTENDED" badge

## Automatic Cleanup Setup

### Manual Execution
```bash
php cleanup_expired_quizzes.php
```

### Scheduled Execution (Linux/Mac - Cron Job)
Add to crontab to run every hour:
```bash
0 * * * * cd /path/to/Quiz-Master-Hub && php cleanup_expired_quizzes.php >> cleanup.log 2>&1
```

Run every 6 hours:
```bash
0 */6 * * * cd /path/to/Quiz-Master-Hub && php cleanup_expired_quizzes.php >> cleanup.log 2>&1
```

### Scheduled Execution (Windows - Task Scheduler)
1. Create a batch file `run_cleanup.bat`:
```batch
@echo off
cd C:\path\to\Quiz-Master-Hub
php cleanup_expired_quizzes.php >> cleanup.log 2>&1
```

2. Set up Windows Task Scheduler to run this batch file at desired intervals

## Testing the Feature

### Method 1: Automated Test
```bash
php test_expiration_feature.php
```
This will:
- Create a test quiz with past due date
- Verify it's filtered from student view
- Run cleanup
- Verify not_attended records are created
- Verify quiz is deleted

### Method 2: Manual Testing
1. Create a quiz with a due date/time from 1 minute ago
2. As a student, verify the quiz doesn't appear in "Available Quizzes"
3. Try accessing the quiz directly: `quiz.php?quiz_id=<id>`
4. Should see "Quiz Expired" error
5. Run cleanup script manually
6. Verify results show "NOT ATTENDED" status

## Data Security & College Isolation

All queries include college-based filtering:
- Expired quizzes only from same college are processed
- Results only show for student's college
- Non-attended records created only for students in the quiz's college
- No cross-college data leakage

## API Endpoints

### Manual Cleanup Trigger
**URL**: `cleanup_expired_quizzes.php`
**Method**: GET
**Access**: Public (should be restricted in production)
**Returns**: Text output showing cleanup status

In production, this should be:
- Restricted to logged-in teachers or admins
- Protected with authentication token
- Accessible only via scheduled tasks

## Troubleshooting

### Quizzes not being deleted
- Check `due_datetime` format in database
- Verify cleanup script is being run
- Check file permissions

### Non-attended records not created
- Ensure students exist in the database
- Verify college field matches in all collections
- Check MongoDB connection

### Students still seeing expired quizzes
- Check whether `due_datetime` is set in quiz document
- Verify server time is correct
- Clear browser cache

## Example Scenario

**Quiz Published**
- Title: "Math Fundamentals"
- Due Date: 2025-03-15
- Due Time: 14:30
- Questions: 10
- Students in college: 5

**Scenario Timeline**
- 2025-03-15 10:00 - Students see quiz in dashboard with countdown
- 2025-03-15 14:00 - Two students complete quiz (scores recorded)
- 2025-03-15 14:45 - Cleanup script runs
  - Finds quiz is expired (14:45 > 14:30)
  - Creates 3 not_attended records for non-completing students
  - Deletes quiz and 10 questions
  
**Results**
- All 5 students have results visible:
  - 2 with "COMPLETED" status and scores
  - 3 with "NOT ATTENDED" status and 0/10

## Future Enhancements

Possible improvements:
1. Allow partial quiz submission after deadline (with penalties)
2. Grace period feature (e.g., +5 minutes to submit)
3. Email notifications when quiz is about to expire
4. Allow teachers to extend quiz deadline
5. Configurable cleanup schedule per quiz
6. Audit log of cleanup operations
7. Restore recently deleted quizzes within 7 days
