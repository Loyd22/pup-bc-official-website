-- Clean up test/placeholder announcements and events
-- This removes entries with test data like "fdsafasd", "sdsadfasdfsadf", etc.

-- Delete test announcements
DELETE FROM announcements 
WHERE title LIKE '%fdsa%' 
   OR title LIKE '%sdsad%'
   OR title LIKE '%test%'
   OR LENGTH(TRIM(title)) < 3
   OR title = '';

-- Delete test events
DELETE FROM events 
WHERE title LIKE '%fdsa%' 
   OR title LIKE '%sdsad%'
   OR title LIKE '%test%'
   OR LENGTH(TRIM(title)) < 3
   OR title = '';

