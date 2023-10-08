TRUNCATE TABLE public.users CASCADE;

-- Generate 5 000 000 users
INSERT INTO public.users(username, email, validts, confirmed)
SELECT 'User ' || generate_series
     , 'user' || generate_series || '@mail.com'
     , CASE WHEN random() > 0.80 THEN now() END
     , CASE WHEN random() > 0.85 THEN TRUE END
FROM generate_series(1, 5000000);

UPDATE public.users
SET
    checked = CASE WHEN random() > 0.5 THEN TRUE ELSE FALSE END,
    validts = now() + (round((random() * 5)::numeric, 2) || ' day')::interval
WHERE validts IS NOT NULL
  AND confirmed;

UPDATE public.users
SET valid = CASE WHEN random() > 0.5 THEN TRUE ELSE FALSE END
WHERE checked;
