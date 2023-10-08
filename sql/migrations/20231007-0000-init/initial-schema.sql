-- Create roles
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = '${karma_owner_role}') THEN
	    CREATE ROLE ${karma_owner_role};
    END IF;
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'liquibase') THEN
        GRANT ${karma_owner_role} TO liquibase;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = '${karma_viewer_role}') THEN
        CREATE ROLE ${karma_viewer_role};
        GRANT SELECT ON ALL TABLES IN SCHEMA public TO ${karma_viewer_role};
        ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO ${karma_viewer_role};
    END IF;
END
$$;

-- Create table users
CREATE TABLE IF NOT EXISTS public.users (
    id bigserial PRIMARY KEY,
    username character varying(255) NOT NULL,
    email character varying(255) UNIQUE NOT NULL,
    validts timestamp with time zone DEFAULT NULL,
    confirmed boolean DEFAULT false,
    checked boolean DEFAULT false,
    valid boolean DEFAULT false
);

CREATE INDEX IF NOT EXISTS users_validts_idx
    ON public.users(validts);

ALTER TABLE public.users OWNER TO ${karma_owner_role};


-- Create table email_check_queue
CREATE TABLE IF NOT EXISTS public.email_check_queue (
    email character varying(255) NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    FOREIGN KEY (email) REFERENCES public.users(email) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS email_check_queue_email_idx
    ON public.email_check_queue(email);

ALTER TABLE public.email_check_queue OWNER TO ${karma_owner_role};


-- Create letter_type
DO $$ BEGIN
    CREATE TYPE letter_type AS ENUM ('firstLetter', 'secondLetter');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;


-- Create table email_send_queue
CREATE TABLE IF NOT EXISTS public.email_send_queue (
    email character varying(255) NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    type public.letter_type,
    FOREIGN KEY (email) REFERENCES public.users(email) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS email_send_queue_email_idx
    ON public.email_send_queue(email);

ALTER TABLE public.email_send_queue OWNER TO ${karma_owner_role};


-- Create table sent_letters
CREATE TABLE IF NOT EXISTS public.sent_letters (
    email character varying(255) NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    type public.letter_type,
    FOREIGN KEY (email) REFERENCES public.users(email) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS sent_letters_email_idx
    ON public.sent_letters(email);

ALTER TABLE public.sent_letters OWNER TO ${karma_owner_role};


-- Create function to get emails list
CREATE OR REPLACE FUNCTION public.get_emails_for_notification(
    letter_type public.letter_type,
    max_days integer,
    min_days integer DEFAULT 0,
    period integer DEFAULT 30,
    limit_num integer DEFAULT 100
)
    RETURNS table
            (
                email character varying,
                valid boolean
            )
    LANGUAGE plpgsql
AS
$$
BEGIN
    RETURN QUERY
        SELECT u.email, u.valid
        FROM users AS u
            LEFT JOIN email_check_queue AS c USING (email)
            LEFT JOIN email_send_queue AS s USING (email)
            LEFT JOIN sent_letters AS l
                ON l.email = u.email
                    AND l.type = letter_type
                    AND l.created_at > now() - (period || ' days')::interval
        WHERE confirmed = TRUE
          AND validts IS NOT NULL
          AND (validts - NOW()) < (max_days || ' days')::interval
          AND (validts - NOW()) > (min_days || ' days')::interval
          AND (u.valid OR (NOT u.valid AND NOT checked))
          AND c.email IS NULL
          AND s.email IS NULL
          AND l.email IS NULL
        ORDER BY validts
        LIMIT limit_num;
END;
$$;

ALTER FUNCTION public.get_emails_for_notification(public.letter_type, integer, integer, integer, integer)
    OWNER TO ${karma_owner_role};
