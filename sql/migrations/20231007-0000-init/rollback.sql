DROP TABLE IF EXISTS public.sent_letters;
DROP TABLE IF EXISTS public.email_send_queue;
DROP TABLE IF EXISTS public.email_check_queue;
DROP TABLE IF EXISTS public.users;

DROP FUNCTION IF EXISTS public.get_emails_for_notification(public.letter_type, integer, integer, integer, integer);

DROP TYPE IF EXISTS public.letter_type;