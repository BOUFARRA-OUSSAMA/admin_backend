--
-- PostgreSQL database dump
--

-- Dumped from database version 17.4
-- Dumped by pg_dump version 17.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: activity_log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.activity_log (
    id bigint NOT NULL,
    log_name character varying(255),
    description text NOT NULL,
    subject_type character varying(255),
    subject_id bigint,
    causer_type character varying(255),
    causer_id bigint,
    properties json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    event character varying(255),
    batch_uuid uuid
);


--
-- Name: activity_log_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.activity_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: activity_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.activity_log_id_seq OWNED BY public.activity_log.id;


--
-- Name: activity_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.activity_logs (
    id bigint NOT NULL,
    user_id bigint,
    action character varying(255) NOT NULL,
    module character varying(255) NOT NULL,
    description text,
    entity_type character varying(255),
    entity_id bigint,
    ip_address character varying(255),
    old_values json,
    new_values json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: activity_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.activity_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: activity_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.activity_logs_id_seq OWNED BY public.activity_logs.id;


--
-- Name: ai_analyses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_analyses (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    patient_id bigint,
    ai_model_id bigint NOT NULL,
    condition_type character varying(255) NOT NULL,
    image_path character varying(255),
    diagnosis character varying(255) NOT NULL,
    confidence double precision NOT NULL,
    report_data json,
    summary text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ai_analyses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ai_analyses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ai_analyses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ai_analyses_id_seq OWNED BY public.ai_analyses.id;


--
-- Name: ai_models; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_models (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    api_identifier character varying(255) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    config json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ai_models_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ai_models_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ai_models_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ai_models_id_seq OWNED BY public.ai_models.id;


--
-- Name: appointments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.appointments (
    id bigint NOT NULL,
    patient_user_id bigint NOT NULL,
    doctor_user_id bigint NOT NULL,
    appointment_datetime_start timestamp(0) without time zone NOT NULL,
    appointment_datetime_end timestamp(0) without time zone NOT NULL,
    type character varying(255),
    reason_for_visit text,
    status character varying(255) DEFAULT 'scheduled'::character varying NOT NULL,
    cancellation_reason text,
    notes_by_patient text,
    notes_by_staff text,
    booked_by_user_id bigint,
    last_updated_by_user_id bigint,
    reminder_sent boolean DEFAULT false NOT NULL,
    reminder_sent_at timestamp(0) without time zone,
    verification_code character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: appointments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.appointments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: appointments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.appointments_id_seq OWNED BY public.appointments.id;


--
-- Name: bill_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bill_items (
    id bigint NOT NULL,
    bill_id bigint NOT NULL,
    service_type character varying(255) NOT NULL,
    description character varying(255),
    price numeric(10,2) NOT NULL,
    quantity integer DEFAULT 1 NOT NULL,
    total numeric(10,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: bill_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bill_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bill_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bill_items_id_seq OWNED BY public.bill_items.id;


--
-- Name: bills; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bills (
    id bigint NOT NULL,
    patient_id bigint NOT NULL,
    doctor_user_id bigint,
    bill_number character varying(255) NOT NULL,
    amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    issue_date date NOT NULL,
    payment_method character varying(255),
    description text,
    pdf_path character varying(255),
    created_by_user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: bills_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bills_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bills_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bills_id_seq OWNED BY public.bills.id;


--
-- Name: blocked_time_slots; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.blocked_time_slots (
    id bigint NOT NULL,
    doctor_user_id bigint NOT NULL,
    start_datetime timestamp(0) without time zone NOT NULL,
    end_datetime timestamp(0) without time zone NOT NULL,
    reason character varying(255),
    block_type character varying(255) DEFAULT 'personal'::character varying NOT NULL,
    is_recurring boolean DEFAULT false NOT NULL,
    recurring_pattern character varying(255),
    recurring_end_date date,
    created_by_user_id bigint,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: blocked_time_slots_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.blocked_time_slots_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: blocked_time_slots_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.blocked_time_slots_id_seq OWNED BY public.blocked_time_slots.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: doctors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.doctors (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    specialty character varying(255) NOT NULL,
    license_number character varying(255) NOT NULL,
    experience_years integer DEFAULT 0 NOT NULL,
    consultation_fee numeric(8,2),
    max_patient_appointments integer DEFAULT 10 NOT NULL,
    is_available boolean DEFAULT true NOT NULL,
    working_hours json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: doctors_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.doctors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: doctors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.doctors_id_seq OWNED BY public.doctors.id;


--
-- Name: expenses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.expenses (
    id bigint NOT NULL,
    expense_type character varying(255) NOT NULL,
    amount numeric(10,2) NOT NULL,
    date date NOT NULL,
    description text,
    payment_method character varying(255),
    receipt_path character varying(255),
    created_by_user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: expenses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.expenses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: expenses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.expenses_id_seq OWNED BY public.expenses.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: jwt_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jwt_tokens (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    token_id character varying(255) NOT NULL,
    is_revoked boolean DEFAULT false NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: jwt_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jwt_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jwt_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jwt_tokens_id_seq OWNED BY public.jwt_tokens.id;


--
-- Name: lab_results; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lab_results (
    id bigint NOT NULL,
    lab_test_id bigint NOT NULL,
    medical_record_id bigint,
    result_date date NOT NULL,
    performed_by_lab_name character varying(255),
    result_document_path character varying(255),
    structured_results json,
    interpretation text,
    reviewed_by_user_id bigint,
    status character varying(255) DEFAULT 'pending_review'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    patient_id bigint
);


--
-- Name: lab_results_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lab_results_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lab_results_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lab_results_id_seq OWNED BY public.lab_results.id;


--
-- Name: lab_tests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lab_tests (
    id bigint NOT NULL,
    chart_patient_id bigint,
    requested_by_user_id bigint NOT NULL,
    test_name character varying(255) NOT NULL,
    test_code character varying(255),
    urgency character varying(255) DEFAULT 'routine'::character varying NOT NULL,
    requested_date timestamp(0) without time zone NOT NULL,
    scheduled_date timestamp(0) without time zone,
    lab_name character varying(255),
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    patient_id bigint
);


--
-- Name: lab_tests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lab_tests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lab_tests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lab_tests_id_seq OWNED BY public.lab_tests.id;


--
-- Name: medical_histories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.medical_histories (
    id bigint NOT NULL,
    patient_id bigint NOT NULL,
    current_medical_conditions json,
    past_surgeries json,
    chronic_diseases json,
    current_medications json,
    allergies json,
    last_updated timestamp(0) without time zone,
    updated_by_user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: medical_histories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.medical_histories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: medical_histories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.medical_histories_id_seq OWNED BY public.medical_histories.id;


--
-- Name: medical_images; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.medical_images (
    id bigint NOT NULL,
    medical_record_id bigint NOT NULL,
    file_path character varying(255) NOT NULL,
    original_file_name character varying(255),
    caption character varying(255),
    taken_date date NOT NULL,
    taken_by_person_name character varying(255),
    uploaded_by_user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: medical_images_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.medical_images_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: medical_images_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.medical_images_id_seq OWNED BY public.medical_images.id;


--
-- Name: medical_records; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.medical_records (
    id bigint NOT NULL,
    patient_user_id bigint NOT NULL,
    created_by_user_id bigint,
    record_type_id bigint NOT NULL,
    chart_patient_id bigint,
    title character varying(255) NOT NULL,
    description text,
    metadata json,
    record_date timestamp(0) without time zone NOT NULL,
    is_confidential boolean DEFAULT false NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    version character varying(255) DEFAULT '1.0'::character varying NOT NULL,
    recordable_type character varying(255),
    recordable_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: medical_records_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.medical_records_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: medical_records_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.medical_records_id_seq OWNED BY public.medical_records.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    data text NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: patient_alerts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.patient_alerts (
    id bigint NOT NULL,
    patient_id bigint NOT NULL,
    alert_type character varying(255) DEFAULT 'warning'::character varying NOT NULL,
    severity character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    title character varying(255) NOT NULL,
    description text NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT patient_alerts_alert_type_check CHECK (((alert_type)::text = ANY ((ARRAY['allergy'::character varying, 'medication'::character varying, 'condition'::character varying, 'warning'::character varying])::text[]))),
    CONSTRAINT patient_alerts_severity_check CHECK (((severity)::text = ANY ((ARRAY['low'::character varying, 'medium'::character varying, 'high'::character varying, 'critical'::character varying])::text[])))
);


--
-- Name: patient_alerts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.patient_alerts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: patient_alerts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.patient_alerts_id_seq OWNED BY public.patient_alerts.id;


--
-- Name: patient_files; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.patient_files (
    id bigint NOT NULL,
    patient_id bigint NOT NULL,
    uploaded_by_user_id bigint NOT NULL,
    file_type character varying(255) DEFAULT 'document'::character varying NOT NULL,
    category character varying(255) DEFAULT 'other'::character varying NOT NULL,
    original_filename character varying(255) NOT NULL,
    stored_filename character varying(255) NOT NULL,
    file_path character varying(500) NOT NULL,
    file_size bigint NOT NULL,
    mime_type character varying(100) NOT NULL,
    description text,
    is_visible_to_patient boolean DEFAULT true NOT NULL,
    uploaded_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT patient_files_category_check CHECK (((category)::text = ANY ((ARRAY['xray'::character varying, 'scan'::character varying, 'lab_report'::character varying, 'insurance'::character varying, 'other'::character varying])::text[]))),
    CONSTRAINT patient_files_file_type_check CHECK (((file_type)::text = ANY ((ARRAY['image'::character varying, 'document'::character varying])::text[])))
);


--
-- Name: patient_files_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.patient_files_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: patient_files_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.patient_files_id_seq OWNED BY public.patient_files.id;


--
-- Name: patient_notes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.patient_notes (
    id bigint NOT NULL,
    patient_id bigint NOT NULL,
    doctor_id bigint NOT NULL,
    note_type character varying(255) DEFAULT 'general'::character varying NOT NULL,
    title character varying(255) NOT NULL,
    content text NOT NULL,
    is_private boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT patient_notes_note_type_check CHECK (((note_type)::text = ANY ((ARRAY['general'::character varying, 'diagnosis'::character varying, 'treatment'::character varying, 'follow_up'::character varying])::text[])))
);


--
-- Name: patient_notes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.patient_notes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: patient_notes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.patient_notes_id_seq OWNED BY public.patient_notes.id;


--
-- Name: patients; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.patients (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    registration_date date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: patients_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.patients_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: patients_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.patients_id_seq OWNED BY public.patients.id;


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    "group" character varying(255),
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: personal_infos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.personal_infos (
    id bigint NOT NULL,
    patient_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    surname character varying(255) NOT NULL,
    birthdate date,
    gender character varying(255),
    address text,
    emergency_contact character varying(255),
    marital_status character varying(255),
    blood_type character varying(5),
    nationality character varying(255),
    profile_image character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT personal_infos_gender_check CHECK (((gender)::text = ANY ((ARRAY['male'::character varying, 'female'::character varying, 'other'::character varying])::text[])))
);


--
-- Name: personal_infos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_infos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_infos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_infos_id_seq OWNED BY public.personal_infos.id;


--
-- Name: prescriptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.prescriptions (
    id bigint NOT NULL,
    chart_patient_id bigint,
    doctor_user_id bigint NOT NULL,
    medication_name character varying(255) NOT NULL,
    dosage character varying(255) NOT NULL,
    frequency character varying(255) NOT NULL,
    duration character varying(255),
    start_date date NOT NULL,
    end_date date,
    instructions text,
    refills_allowed character varying(255),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    patient_id bigint
);


--
-- Name: prescriptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.prescriptions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: prescriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.prescriptions_id_seq OWNED BY public.prescriptions.id;


--
-- Name: record_attachments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.record_attachments (
    id bigint NOT NULL,
    medical_record_id bigint NOT NULL,
    uploaded_by_user_id bigint,
    file_name character varying(255) NOT NULL,
    file_path character varying(255) NOT NULL,
    mime_type character varying(255),
    file_size bigint,
    original_name character varying(255),
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: record_attachments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.record_attachments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: record_attachments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.record_attachments_id_seq OWNED BY public.record_attachments.id;


--
-- Name: record_types; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.record_types (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    description text,
    metadata_schema json,
    requires_attachment boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: record_types_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.record_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: record_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.record_types_id_seq OWNED BY public.record_types.id;


--
-- Name: record_versions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.record_versions (
    id bigint NOT NULL,
    medical_record_id bigint NOT NULL,
    updated_by_user_id bigint,
    version_number character varying(255) NOT NULL,
    change_description text,
    data_snapshot json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: record_versions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.record_versions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: record_versions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.record_versions_id_seq OWNED BY public.record_versions.id;


--
-- Name: reminder_analytics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reminder_analytics (
    id bigint NOT NULL,
    analytics_date date NOT NULL,
    doctor_id bigint,
    reminders_sent integer DEFAULT 0 NOT NULL,
    reminders_delivered integer DEFAULT 0 NOT NULL,
    reminders_failed integer DEFAULT 0 NOT NULL,
    reminders_opened integer DEFAULT 0 NOT NULL,
    reminders_clicked integer DEFAULT 0 NOT NULL,
    email_sent integer DEFAULT 0 NOT NULL,
    push_sent integer DEFAULT 0 NOT NULL,
    sms_sent integer DEFAULT 0 NOT NULL,
    in_app_sent integer DEFAULT 0 NOT NULL,
    appointments_kept integer DEFAULT 0 NOT NULL,
    appointments_cancelled integer DEFAULT 0 NOT NULL,
    appointments_no_show integer DEFAULT 0 NOT NULL,
    appointments_rescheduled integer DEFAULT 0 NOT NULL,
    delivery_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    open_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    click_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    attendance_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    avg_response_time integer,
    fastest_response_time integer,
    slowest_response_time integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: reminder_analytics_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reminder_analytics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reminder_analytics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reminder_analytics_id_seq OWNED BY public.reminder_analytics.id;


--
-- Name: reminder_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reminder_logs (
    id bigint NOT NULL,
    appointment_id bigint NOT NULL,
    user_id bigint NOT NULL,
    reminder_type character varying(255) DEFAULT '24h'::character varying NOT NULL,
    channel character varying(255) DEFAULT 'email'::character varying NOT NULL,
    trigger_type character varying(255) DEFAULT 'automatic'::character varying NOT NULL,
    scheduled_for timestamp(0) without time zone,
    sent_at timestamp(0) without time zone,
    delivery_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    subject character varying(255),
    message_content text,
    job_id character varying(255),
    metadata json,
    error_message text,
    retry_count integer DEFAULT 0 NOT NULL,
    last_retry_at timestamp(0) without time zone,
    opened_at timestamp(0) without time zone,
    clicked_at timestamp(0) without time zone,
    tracking_token character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT reminder_logs_channel_check CHECK (((channel)::text = ANY ((ARRAY['email'::character varying, 'push'::character varying, 'sms'::character varying, 'in_app'::character varying])::text[]))),
    CONSTRAINT reminder_logs_delivery_status_check CHECK (((delivery_status)::text = ANY ((ARRAY['pending'::character varying, 'sent'::character varying, 'delivered'::character varying, 'failed'::character varying, 'bounced'::character varying, 'cancelled'::character varying])::text[]))),
    CONSTRAINT reminder_logs_reminder_type_check CHECK (((reminder_type)::text = ANY ((ARRAY['24h'::character varying, '2h'::character varying, 'manual'::character varying, 'custom'::character varying])::text[]))),
    CONSTRAINT reminder_logs_trigger_type_check CHECK (((trigger_type)::text = ANY ((ARRAY['automatic'::character varying, 'manual'::character varying])::text[])))
);


--
-- Name: reminder_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reminder_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reminder_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reminder_logs_id_seq OWNED BY public.reminder_logs.id;


--
-- Name: reminder_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reminder_settings (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    user_type character varying(255) DEFAULT 'patient'::character varying NOT NULL,
    email_enabled boolean DEFAULT true NOT NULL,
    push_enabled boolean DEFAULT true NOT NULL,
    sms_enabled boolean DEFAULT false NOT NULL,
    first_reminder_hours integer DEFAULT 24 NOT NULL,
    second_reminder_hours integer DEFAULT 2 NOT NULL,
    reminder_24h_enabled boolean DEFAULT true NOT NULL,
    reminder_2h_enabled boolean DEFAULT true NOT NULL,
    preferred_channels json,
    timezone character varying(255) DEFAULT 'UTC'::character varying NOT NULL,
    custom_settings json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT reminder_settings_user_type_check CHECK (((user_type)::text = ANY ((ARRAY['patient'::character varying, 'doctor'::character varying, 'global'::character varying])::text[])))
);


--
-- Name: reminder_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reminder_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reminder_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reminder_settings_id_seq OWNED BY public.reminder_settings.id;


--
-- Name: role_permission; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_permission (
    role_id bigint NOT NULL,
    permission_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: scanned_scripts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.scanned_scripts (
    id bigint NOT NULL,
    medical_record_id bigint NOT NULL,
    file_path character varying(255) NOT NULL,
    original_file_name character varying(255),
    doctor_name_on_script character varying(255),
    prescription_date date NOT NULL,
    expiration_date date,
    notes text,
    uploaded_by_user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: scanned_scripts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.scanned_scripts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: scanned_scripts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.scanned_scripts_id_seq OWNED BY public.scanned_scripts.id;


--
-- Name: scheduled_reminder_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.scheduled_reminder_jobs (
    id bigint NOT NULL,
    appointment_id bigint NOT NULL,
    reminder_log_id bigint,
    job_id character varying(255) NOT NULL,
    queue_job_id character varying(255),
    reminder_type character varying(255) DEFAULT '24h'::character varying NOT NULL,
    channel character varying(255) DEFAULT 'email'::character varying NOT NULL,
    scheduled_for timestamp(0) without time zone NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    attempts integer DEFAULT 0 NOT NULL,
    max_attempts integer DEFAULT 3 NOT NULL,
    last_attempted_at timestamp(0) without time zone,
    executed_at timestamp(0) without time zone,
    failed_at timestamp(0) without time zone,
    job_payload json,
    failure_reason text,
    is_cancelled boolean DEFAULT false NOT NULL,
    cancelled_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT scheduled_reminder_jobs_channel_check CHECK (((channel)::text = ANY ((ARRAY['email'::character varying, 'sms'::character varying, 'push'::character varying])::text[]))),
    CONSTRAINT scheduled_reminder_jobs_reminder_type_check CHECK (((reminder_type)::text = ANY ((ARRAY['24h'::character varying, '2h'::character varying, 'manual'::character varying, 'custom'::character varying])::text[]))),
    CONSTRAINT scheduled_reminder_jobs_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'processing'::character varying, 'sent'::character varying, 'failed'::character varying, 'cancelled'::character varying, 'expired'::character varying])::text[])))
);


--
-- Name: scheduled_reminder_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.scheduled_reminder_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: scheduled_reminder_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.scheduled_reminder_jobs_id_seq OWNED BY public.scheduled_reminder_jobs.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: staff_profiles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_profiles (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    staff_internal_id character varying(255),
    department character varying(255),
    "position" character varying(255),
    responsibilities text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: staff_profiles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.staff_profiles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: staff_profiles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.staff_profiles_id_seq OWNED BY public.staff_profiles.id;


--
-- Name: stock_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.stock_items (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    category character varying(255),
    description text,
    quantity integer DEFAULT 0 NOT NULL,
    unit_price numeric(10,2) NOT NULL,
    unit_type character varying(255) DEFAULT 'piece'::character varying NOT NULL,
    purchase_date date NOT NULL,
    expiry_date date,
    supplier character varying(255),
    location character varying(255),
    created_by_user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: stock_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stock_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stock_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stock_items_id_seq OWNED BY public.stock_items.id;


--
-- Name: stock_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.stock_transactions (
    id bigint NOT NULL,
    stock_item_id bigint NOT NULL,
    transaction_type character varying(255) NOT NULL,
    quantity integer NOT NULL,
    notes text,
    performed_by_user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: stock_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stock_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stock_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stock_transactions_id_seq OWNED BY public.stock_transactions.id;


--
-- Name: timeline_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.timeline_events (
    id bigint NOT NULL,
    patient_id bigint NOT NULL,
    event_type character varying(255) DEFAULT 'manual'::character varying NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    event_date timestamp(0) without time zone NOT NULL,
    related_id bigint,
    related_type character varying(255),
    importance character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    is_visible_to_patient boolean DEFAULT true NOT NULL,
    created_by_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT timeline_events_event_type_check CHECK (((event_type)::text = ANY (ARRAY[('appointment'::character varying)::text, ('prescription'::character varying)::text, ('vital_signs'::character varying)::text, ('note'::character varying)::text, ('file_upload'::character varying)::text, ('alert'::character varying)::text, ('manual'::character varying)::text]))),
    CONSTRAINT timeline_events_importance_check CHECK (((importance)::text = ANY ((ARRAY['low'::character varying, 'medium'::character varying, 'high'::character varying])::text[])))
);


--
-- Name: timeline_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.timeline_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: timeline_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.timeline_events_id_seq OWNED BY public.timeline_events.id;


--
-- Name: treatments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.treatments (
    id bigint NOT NULL,
    chart_patient_id bigint,
    prescribed_by_user_id bigint NOT NULL,
    treatment_name character varying(255) NOT NULL,
    description text,
    start_date date NOT NULL,
    end_date date,
    instructions text,
    duration character varying(255),
    status character varying(255) DEFAULT 'ongoing'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    patient_id bigint
);


--
-- Name: treatments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.treatments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: treatments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.treatments_id_seq OWNED BY public.treatments.id;


--
-- Name: user_ai_model; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_ai_model (
    user_id bigint NOT NULL,
    ai_model_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_role; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_role (
    user_id bigint NOT NULL,
    role_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    last_login_at timestamp(0) without time zone,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    phone character varying(255),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    deleted_at timestamp(0) without time zone,
    password_change_required boolean DEFAULT false NOT NULL,
    CONSTRAINT users_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying, 'pending'::character varying])::text[])))
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: vital_signs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vital_signs (
    id bigint NOT NULL,
    patient_id bigint NOT NULL,
    recorded_by_user_id bigint,
    blood_pressure_systolic smallint,
    blood_pressure_diastolic smallint,
    pulse_rate smallint,
    temperature numeric(4,1),
    temperature_unit character varying(10) DEFAULT '°C'::character varying NOT NULL,
    respiratory_rate smallint,
    oxygen_saturation smallint,
    weight numeric(5,2),
    weight_unit character varying(10) DEFAULT 'kg'::character varying NOT NULL,
    height numeric(5,2),
    height_unit character varying(10) DEFAULT 'cm'::character varying NOT NULL,
    notes text,
    recorded_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: vital_signs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vital_signs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vital_signs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vital_signs_id_seq OWNED BY public.vital_signs.id;


--
-- Name: activity_log id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_log ALTER COLUMN id SET DEFAULT nextval('public.activity_log_id_seq'::regclass);


--
-- Name: activity_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_logs ALTER COLUMN id SET DEFAULT nextval('public.activity_logs_id_seq'::regclass);


--
-- Name: ai_analyses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_analyses ALTER COLUMN id SET DEFAULT nextval('public.ai_analyses_id_seq'::regclass);


--
-- Name: ai_models id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_models ALTER COLUMN id SET DEFAULT nextval('public.ai_models_id_seq'::regclass);


--
-- Name: appointments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointments ALTER COLUMN id SET DEFAULT nextval('public.appointments_id_seq'::regclass);


--
-- Name: bill_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_items ALTER COLUMN id SET DEFAULT nextval('public.bill_items_id_seq'::regclass);


--
-- Name: bills id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bills ALTER COLUMN id SET DEFAULT nextval('public.bills_id_seq'::regclass);


--
-- Name: blocked_time_slots id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blocked_time_slots ALTER COLUMN id SET DEFAULT nextval('public.blocked_time_slots_id_seq'::regclass);


--
-- Name: doctors id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.doctors ALTER COLUMN id SET DEFAULT nextval('public.doctors_id_seq'::regclass);


--
-- Name: expenses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses ALTER COLUMN id SET DEFAULT nextval('public.expenses_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: jwt_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jwt_tokens ALTER COLUMN id SET DEFAULT nextval('public.jwt_tokens_id_seq'::regclass);


--
-- Name: lab_results id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_results ALTER COLUMN id SET DEFAULT nextval('public.lab_results_id_seq'::regclass);


--
-- Name: lab_tests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_tests ALTER COLUMN id SET DEFAULT nextval('public.lab_tests_id_seq'::regclass);


--
-- Name: medical_histories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medical_histories ALTER COLUMN id SET DEFAULT nextval('public.medical_histories_id_seq'::regclass);


--
-- Name: medical_images id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medical_images ALTER COLUMN id SET DEFAULT nextval('public.medical_images_id_seq'::regclass);


--
-- Name: medical_records id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medical_records ALTER COLUMN id SET DEFAULT nextval('public.medical_records_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: patient_alerts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_alerts ALTER COLUMN id SET DEFAULT nextval('public.patient_alerts_id_seq'::regclass);


--
-- Name: patient_files id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_files ALTER COLUMN id SET DEFAULT nextval('public.patient_files_id_seq'::regclass);


--
-- Name: patient_notes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_notes ALTER COLUMN id SET DEFAULT nextval('public.patient_notes_id_seq'::regclass);


--
-- Name: patients id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patients ALTER COLUMN id SET DEFAULT nextval('public.patients_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: personal_infos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_infos ALTER COLUMN id SET DEFAULT nextval('public.personal_infos_id_seq'::regclass);


--
-- Name: prescriptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prescriptions ALTER COLUMN id SET DEFAULT nextval('public.prescriptions_id_seq'::regclass);


--
-- Name: record_attachments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.record_attachments ALTER COLUMN id SET DEFAULT nextval('public.record_attachments_id_seq'::regclass);


--
-- Name: record_types id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.record_types ALTER COLUMN id SET DEFAULT nextval('public.record_types_id_seq'::regclass);


--
-- Name: record_versions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.record_versions ALTER COLUMN id SET DEFAULT nextval('public.record_versions_id_seq'::regclass);


--
-- Name: reminder_analytics id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminder_analytics ALTER COLUMN id SET DEFAULT nextval('public.reminder_analytics_id_seq'::regclass);


--
-- Name: reminder_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminder_logs ALTER COLUMN id SET DEFAULT nextval('public.reminder_logs_id_seq'::regclass);


--
-- Name: reminder_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminder_settings ALTER COLUMN id SET DEFAULT nextval('public.reminder_settings_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: scanned_scripts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scanned_scripts ALTER COLUMN id SET DEFAULT nextval('public.scanned_scripts_id_seq'::regclass);


--
-- Name: scheduled_reminder_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scheduled_reminder_jobs ALTER COLUMN id SET DEFAULT nextval('public.scheduled_reminder_jobs_id_seq'::regclass);


--
-- Name: staff_profiles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_profiles ALTER COLUMN id SET DEFAULT nextval('public.staff_profiles_id_seq'::regclass);


--
-- Name: stock_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_items ALTER COLUMN id SET DEFAULT nextval('public.stock_items_id_seq'::regclass);


--
-- Name: stock_transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_transactions ALTER COLUMN id SET DEFAULT nextval('public.stock_transactions_id_seq'::regclass);


--
-- Name: timeline_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.timeline_events ALTER COLUMN id SET DEFAULT nextval('public.timeline_events_id_seq'::regclass);


--
-- Name: treatments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.treatments ALTER COLUMN id SET DEFAULT nextval('public.treatments_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: vital_signs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vital_signs ALTER COLUMN id SET DEFAULT nextval('public.vital_signs_id_seq'::regclass);


--
-- Name: activity_log activity_log_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_log
    ADD CONSTRAINT activity_log_pkey PRIMARY KEY (id);


--
-- Name: activity_logs activity_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_logs
    ADD CONSTRAINT activity_logs_pkey PRIMARY KEY (id);


--
-- Name: ai_analyses ai_analyses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_analyses
    ADD CONSTRAINT ai_analyses_pkey PRIMARY KEY (id);


--
-- Name: ai_models ai_models_api_identifier_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_models
    ADD CONSTRAINT ai_models_api_identifier_unique UNIQUE (api_identifier);


--
-- Name: ai_models ai_models_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_models
    ADD CONSTRAINT ai_models_pkey PRIMARY KEY (id);


--
-- Name: appointments appointments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointments
    ADD CONSTRAINT appointments_pkey PRIMARY KEY (id);


--
-- Name: bill_items bill_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_items
    ADD CONSTRAINT bill_items_pkey PRIMARY KEY (id);


--
-- Name: bills bills_bill_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bills
    ADD CONSTRAINT bills_bill_number_unique UNIQUE (bill_number);


--
-- Name: bills bills_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bills
    ADD CONSTRAINT bills_pkey PRIMARY KEY (id);


--
-- Name: blocked_time_slots blocked_time_slots_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blocked_time_slots
    ADD CONSTRAINT blocked_time_slots_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: doctors doctors_license_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.doctors
    ADD CONSTRAINT doctors_license_number_unique UNIQUE (license_number);


--
-- Name: doctors doctors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.doctors
    ADD CONSTRAINT doctors_pkey PRIMARY KEY (id);


--
-- Name: expenses expenses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: jwt_tokens jwt_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jwt_tokens
    ADD CONSTRAINT jwt_tokens_pkey PRIMARY KEY (id);


--
-- Name: lab_results lab_results_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_results
    ADD CONSTRAINT lab_results_pkey PRIMARY KEY (id);


--
-- Name: lab_tests lab_tests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_tests
    ADD CONSTRAINT lab_tests_pkey PRIMARY KEY (id);


--
-- Name: medical_histories medical_histories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medical_histories
    ADD CONSTRAINT medical_histories_pkey PRIMARY KEY (id);


--
-- Name: medical_images medical_images_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medical_images
    ADD CONSTRAINT medical_images_pkey PRIMARY KEY (id);


--
-- Name: medical_records medical_records_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medical_records
    ADD CONSTRAINT medical_records_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: patient_alerts patient_alerts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_alerts
    ADD CONSTRAINT patient_alerts_pkey PRIMARY KEY (id);


--
-- Name: patient_files patient_files_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_files
    ADD CONSTRAINT patient_files_pkey PRIMARY KEY (id);


--
-- Name: patient_notes patient_notes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_notes
    ADD CONSTRAINT patient_notes_pkey PRIMARY KEY (id);


--
-- Name: patients patients_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patients
    ADD CONSTRAINT patients_pkey PRIMARY KEY (id);


--
-- Name: permissions permissions_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_code_unique UNIQUE (code);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: personal_infos personal_infos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_infos
    ADD CONSTRAINT personal_infos_pkey PRIMARY KEY (id);


--
-- Name: prescriptions prescriptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prescriptions
    ADD CONSTRAINT prescriptions_pkey PRIMARY KEY (id);


--
-- Name: record_attachments record_attachments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.record_attachments
    ADD CONSTRAINT record_attachments_pkey PRIMARY KEY (id);


--
-- Name: record_types record_types_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.record_types
    ADD CONSTRAINT record_types_code_unique UNIQUE (code);


--
-- Name: record_types record_types_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.record_types
    ADD CONSTRAINT record_types_pkey PRIMARY KEY (id);


--
-- Name: record_versions record_versions_medical_record_id_version_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.record_versions
    ADD CONSTRAINT record_versions_medical_record_id_version_number_unique UNIQUE (medical_record_id, version_number);


--
-- Name: record_versions record_versions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.record_versions
    ADD CONSTRAINT record_versions_pkey PRIMARY KEY (id);


--
-- Name: reminder_analytics reminder_analytics_analytics_date_doctor_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminder_analytics
    ADD CONSTRAINT reminder_analytics_analytics_date_doctor_id_unique UNIQUE (analytics_date, doctor_id);


--
-- Name: reminder_analytics reminder_analytics_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminder_analytics
    ADD CONSTRAINT reminder_analytics_pkey PRIMARY KEY (id);


--
-- Name: reminder_logs reminder_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminder_logs
    ADD CONSTRAINT reminder_logs_pkey PRIMARY KEY (id);


--
-- Name: reminder_settings reminder_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminder_settings
    ADD CONSTRAINT reminder_settings_pkey PRIMARY KEY (id);


--
-- Name: reminder_settings reminder_settings_user_id_user_type_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminder_settings
    ADD CONSTRAINT reminder_settings_user_id_user_type_unique UNIQUE (user_id, user_type);


--
-- Name: role_permission role_permission_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permission
    ADD CONSTRAINT role_permission_pkey PRIMARY KEY (role_id, permission_id);


--
-- Name: roles roles_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_code_unique UNIQUE (code);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: scanned_scripts scanned_scripts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scanned_scripts
    ADD CONSTRAINT scanned_scripts_pkey PRIMARY KEY (id);


--
-- Name: scheduled_reminder_jobs scheduled_reminder_jobs_job_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scheduled_reminder_jobs
    ADD CONSTRAINT scheduled_reminder_jobs_job_id_unique UNIQUE (job_id);


--
-- Name: scheduled_reminder_jobs scheduled_reminder_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scheduled_reminder_jobs
    ADD CONSTRAINT scheduled_reminder_jobs_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: staff_profiles staff_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_profiles
    ADD CONSTRAINT staff_profiles_pkey PRIMARY KEY (id);


--
-- Name: staff_profiles staff_profiles_staff_internal_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_profiles
    ADD CONSTRAINT staff_profiles_staff_internal_id_unique UNIQUE (staff_internal_id);


--
-- Name: staff_profiles staff_profiles_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_profiles
    ADD CONSTRAINT staff_profiles_user_id_unique UNIQUE (user_id);


--
-- Name: stock_items stock_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_items
    ADD CONSTRAINT stock_items_pkey PRIMARY KEY (id);


--
-- Name: stock_transactions stock_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_transactions
    ADD CONSTRAINT stock_transactions_pkey PRIMARY KEY (id);


--
-- Name: timeline_events timeline_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.timeline_events
    ADD CONSTRAINT timeline_events_pkey PRIMARY KEY (id);


--
-- Name: treatments treatments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.treatments
    ADD CONSTRAINT treatments_pkey PRIMARY KEY (id);


--
-- Name: user_ai_model user_ai_model_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_ai_model
    ADD CONSTRAINT user_ai_model_pkey PRIMARY KEY (user_id, ai_model_id);


--
-- Name: user_role user_role_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_role
    ADD CONSTRAINT user_role_pkey PRIMARY KEY (user_id, role_id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: vital_signs vital_signs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vital_signs
    ADD CONSTRAINT vital_signs_pkey PRIMARY KEY (id);


--
-- Name: activity_log_log_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX activity_log_log_name_index ON public.activity_log USING btree (log_name);


--
-- Name: activity_logs_action_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX activity_logs_action_index ON public.activity_logs USING btree (action);


--
-- Name: activity_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX activity_logs_created_at_index ON public.activity_logs USING btree (created_at);


--
-- Name: activity_logs_entity_type_entity_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX activity_logs_entity_type_entity_id_index ON public.activity_logs USING btree (entity_type, entity_id);


--
-- Name: activity_logs_module_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX activity_logs_module_index ON public.activity_logs USING btree (module);


--
-- Name: appointments_doctor_user_id_appointment_datetime_start_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX appointments_doctor_user_id_appointment_datetime_start_index ON public.appointments USING btree (doctor_user_id, appointment_datetime_start);


--
-- Name: appointments_patient_user_id_appointment_datetime_start_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX appointments_patient_user_id_appointment_datetime_start_index ON public.appointments USING btree (patient_user_id, appointment_datetime_start);


--
-- Name: appointments_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX appointments_status_index ON public.appointments USING btree (status);


--
-- Name: bill_items_bill_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bill_items_bill_id_index ON public.bill_items USING btree (bill_id);


--
-- Name: bills_doctor_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bills_doctor_user_id_index ON public.bills USING btree (doctor_user_id);


--
-- Name: bills_issue_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bills_issue_date_index ON public.bills USING btree (issue_date);


--
-- Name: bills_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bills_patient_id_index ON public.bills USING btree (patient_id);


--
-- Name: blocked_time_slots_block_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX blocked_time_slots_block_type_index ON public.blocked_time_slots USING btree (block_type);


--
-- Name: blocked_time_slots_doctor_user_id_start_datetime_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX blocked_time_slots_doctor_user_id_start_datetime_index ON public.blocked_time_slots USING btree (doctor_user_id, start_datetime);


--
-- Name: blocked_time_slots_is_recurring_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX blocked_time_slots_is_recurring_index ON public.blocked_time_slots USING btree (is_recurring);


--
-- Name: causer; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX causer ON public.activity_log USING btree (causer_type, causer_id);


--
-- Name: doctors_user_id_specialty_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX doctors_user_id_specialty_index ON public.doctors USING btree (user_id, specialty);


--
-- Name: expenses_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX expenses_date_index ON public.expenses USING btree (date);


--
-- Name: expenses_expense_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX expenses_expense_type_index ON public.expenses USING btree (expense_type);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: jwt_tokens_token_id_is_revoked_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jwt_tokens_token_id_is_revoked_index ON public.jwt_tokens USING btree (token_id, is_revoked);


--
-- Name: jwt_tokens_user_id_is_revoked_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jwt_tokens_user_id_is_revoked_index ON public.jwt_tokens USING btree (user_id, is_revoked);


--
-- Name: lab_results_lab_test_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lab_results_lab_test_id_index ON public.lab_results USING btree (lab_test_id);


--
-- Name: lab_results_medical_record_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lab_results_medical_record_id_index ON public.lab_results USING btree (medical_record_id);


--
-- Name: lab_results_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lab_results_patient_id_index ON public.lab_results USING btree (patient_id);


--
-- Name: lab_results_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lab_results_status_index ON public.lab_results USING btree (status);


--
-- Name: lab_tests_chart_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lab_tests_chart_patient_id_index ON public.lab_tests USING btree (chart_patient_id);


--
-- Name: lab_tests_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lab_tests_patient_id_index ON public.lab_tests USING btree (patient_id);


--
-- Name: lab_tests_requested_by_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lab_tests_requested_by_user_id_index ON public.lab_tests USING btree (requested_by_user_id);


--
-- Name: lab_tests_requested_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lab_tests_requested_date_index ON public.lab_tests USING btree (requested_date);


--
-- Name: lab_tests_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lab_tests_status_index ON public.lab_tests USING btree (status);


--
-- Name: medical_histories_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX medical_histories_patient_id_index ON public.medical_histories USING btree (patient_id);


--
-- Name: medical_images_medical_record_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX medical_images_medical_record_id_index ON public.medical_images USING btree (medical_record_id);


--
-- Name: medical_records_chart_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX medical_records_chart_patient_id_index ON public.medical_records USING btree (chart_patient_id);


--
-- Name: medical_records_created_by_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX medical_records_created_by_user_id_index ON public.medical_records USING btree (created_by_user_id);


--
-- Name: medical_records_patient_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX medical_records_patient_user_id_index ON public.medical_records USING btree (patient_user_id);


--
-- Name: medical_records_record_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX medical_records_record_date_index ON public.medical_records USING btree (record_date);


--
-- Name: medical_records_record_type_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX medical_records_record_type_id_index ON public.medical_records USING btree (record_type_id);


--
-- Name: medical_records_recordable_type_recordable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX medical_records_recordable_type_recordable_id_index ON public.medical_records USING btree (recordable_type, recordable_id);


--
-- Name: medical_records_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX medical_records_status_index ON public.medical_records USING btree (status);


--
-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


--
-- Name: patient_alerts_alert_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patient_alerts_alert_type_index ON public.patient_alerts USING btree (alert_type);


--
-- Name: patient_alerts_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patient_alerts_is_active_index ON public.patient_alerts USING btree (is_active);


--
-- Name: patient_alerts_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patient_alerts_patient_id_index ON public.patient_alerts USING btree (patient_id);


--
-- Name: patient_alerts_severity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patient_alerts_severity_index ON public.patient_alerts USING btree (severity);


--
-- Name: patient_files_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patient_files_category_index ON public.patient_files USING btree (category);


--
-- Name: patient_files_file_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patient_files_file_type_index ON public.patient_files USING btree (file_type);


--
-- Name: patient_files_is_visible_to_patient_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patient_files_is_visible_to_patient_index ON public.patient_files USING btree (is_visible_to_patient);


--
-- Name: patient_files_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patient_files_patient_id_index ON public.patient_files USING btree (patient_id);


--
-- Name: patient_files_uploaded_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patient_files_uploaded_at_index ON public.patient_files USING btree (uploaded_at);


--
-- Name: patient_files_uploaded_by_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patient_files_uploaded_by_user_id_index ON public.patient_files USING btree (uploaded_by_user_id);


--
-- Name: patient_notes_doctor_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patient_notes_doctor_id_index ON public.patient_notes USING btree (doctor_id);


--
-- Name: patient_notes_is_private_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patient_notes_is_private_index ON public.patient_notes USING btree (is_private);


--
-- Name: patient_notes_note_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patient_notes_note_type_index ON public.patient_notes USING btree (note_type);


--
-- Name: patient_notes_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patient_notes_patient_id_index ON public.patient_notes USING btree (patient_id);


--
-- Name: patients_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX patients_user_id_index ON public.patients USING btree (user_id);


--
-- Name: permissions_group_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX permissions_group_index ON public.permissions USING btree ("group");


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: personal_infos_name_surname_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_infos_name_surname_index ON public.personal_infos USING btree (name, surname);


--
-- Name: personal_infos_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_infos_patient_id_index ON public.personal_infos USING btree (patient_id);


--
-- Name: prescriptions_chart_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX prescriptions_chart_patient_id_index ON public.prescriptions USING btree (chart_patient_id);


--
-- Name: prescriptions_doctor_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX prescriptions_doctor_user_id_index ON public.prescriptions USING btree (doctor_user_id);


--
-- Name: prescriptions_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX prescriptions_patient_id_index ON public.prescriptions USING btree (patient_id);


--
-- Name: prescriptions_start_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX prescriptions_start_date_index ON public.prescriptions USING btree (start_date);


--
-- Name: prescriptions_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX prescriptions_status_index ON public.prescriptions USING btree (status);


--
-- Name: record_attachments_medical_record_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX record_attachments_medical_record_id_index ON public.record_attachments USING btree (medical_record_id);


--
-- Name: record_types_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX record_types_code_index ON public.record_types USING btree (code);


--
-- Name: record_types_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX record_types_is_active_index ON public.record_types USING btree (is_active);


--
-- Name: record_versions_version_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX record_versions_version_number_index ON public.record_versions USING btree (version_number);


--
-- Name: reminder_analytics_analytics_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reminder_analytics_analytics_date_index ON public.reminder_analytics USING btree (analytics_date);


--
-- Name: reminder_analytics_doctor_id_analytics_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reminder_analytics_doctor_id_analytics_date_index ON public.reminder_analytics USING btree (doctor_id, analytics_date);


--
-- Name: reminder_logs_appointment_id_reminder_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reminder_logs_appointment_id_reminder_type_index ON public.reminder_logs USING btree (appointment_id, reminder_type);


--
-- Name: reminder_logs_delivery_status_scheduled_for_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reminder_logs_delivery_status_scheduled_for_index ON public.reminder_logs USING btree (delivery_status, scheduled_for);


--
-- Name: reminder_logs_job_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reminder_logs_job_id_index ON public.reminder_logs USING btree (job_id);


--
-- Name: reminder_logs_user_id_sent_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reminder_logs_user_id_sent_at_index ON public.reminder_logs USING btree (user_id, sent_at);


--
-- Name: reminder_settings_user_type_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reminder_settings_user_type_is_active_index ON public.reminder_settings USING btree (user_type, is_active);


--
-- Name: scanned_scripts_medical_record_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX scanned_scripts_medical_record_id_index ON public.scanned_scripts USING btree (medical_record_id);


--
-- Name: scheduled_reminder_jobs_appointment_id_reminder_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX scheduled_reminder_jobs_appointment_id_reminder_type_index ON public.scheduled_reminder_jobs USING btree (appointment_id, reminder_type);


--
-- Name: scheduled_reminder_jobs_job_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX scheduled_reminder_jobs_job_id_status_index ON public.scheduled_reminder_jobs USING btree (job_id, status);


--
-- Name: scheduled_reminder_jobs_status_scheduled_for_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX scheduled_reminder_jobs_status_scheduled_for_index ON public.scheduled_reminder_jobs USING btree (status, scheduled_for);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: staff_profiles_department_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX staff_profiles_department_index ON public.staff_profiles USING btree (department);


--
-- Name: staff_profiles_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX staff_profiles_is_active_index ON public.staff_profiles USING btree (is_active);


--
-- Name: stock_items_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stock_items_category_index ON public.stock_items USING btree (category);


--
-- Name: stock_items_expiry_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stock_items_expiry_date_index ON public.stock_items USING btree (expiry_date);


--
-- Name: stock_items_purchase_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stock_items_purchase_date_index ON public.stock_items USING btree (purchase_date);


--
-- Name: stock_transactions_performed_by_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stock_transactions_performed_by_user_id_index ON public.stock_transactions USING btree (performed_by_user_id);


--
-- Name: stock_transactions_stock_item_id_transaction_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stock_transactions_stock_item_id_transaction_type_index ON public.stock_transactions USING btree (stock_item_id, transaction_type);


--
-- Name: subject; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subject ON public.activity_log USING btree (subject_type, subject_id);


--
-- Name: timeline_events_created_by_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timeline_events_created_by_user_id_index ON public.timeline_events USING btree (created_by_user_id);


--
-- Name: timeline_events_event_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timeline_events_event_date_index ON public.timeline_events USING btree (event_date);


--
-- Name: timeline_events_event_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timeline_events_event_type_index ON public.timeline_events USING btree (event_type);


--
-- Name: timeline_events_importance_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timeline_events_importance_index ON public.timeline_events USING btree (importance);


--
-- Name: timeline_events_is_visible_to_patient_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timeline_events_is_visible_to_patient_index ON public.timeline_events USING btree (is_visible_to_patient);


--
-- Name: timeline_events_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timeline_events_patient_id_index ON public.timeline_events USING btree (patient_id);


--
-- Name: timeline_events_related_id_related_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timeline_events_related_id_related_type_index ON public.timeline_events USING btree (related_id, related_type);


--
-- Name: treatments_chart_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX treatments_chart_patient_id_index ON public.treatments USING btree (chart_patient_id);


--
-- Name: treatments_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX treatments_patient_id_index ON public.treatments USING btree (patient_id);


--
-- Name: treatments_prescribed_by_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX treatments_prescribed_by_user_id_index ON public.treatments USING btree (prescribed_by_user_id);


--
-- Name: treatments_start_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX treatments_start_date_index ON public.treatments USING btree (start_date);


--
-- Name: treatments_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX treatments_status_index ON public.treatments USING btree (status);


--
-- Name: users_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_created_at_index ON public.users USING btree (created_at);


--
-- Name: users_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_email_index ON public.users USING btree (email);


--
-- Name: users_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_status_index ON public.users USING btree (status);


--
-- Name: vital_signs_patient_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vital_signs_patient_id_index ON public.vital_signs USING btree (patient_id);


--
-- Name: vital_signs_recorded_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vital_signs_recorded_at_index ON public.vital_signs USING btree (recorded_at);


--
-- Name: activity_logs activity_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_logs
    ADD CONSTRAINT activity_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ai_analyses ai_analyses_ai_model_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_analyses
    ADD CONSTRAINT ai_analyses_ai_model_id_foreign FOREIGN KEY (ai_model_id) REFERENCES public.ai_models(id) ON DELETE CASCADE;


--
-- Name: ai_analyses ai_analyses_patient_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_analyses
    ADD CONSTRAINT ai_analyses_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ai_analyses ai_analyses_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_analyses
    ADD CONSTRAINT ai_analyses_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: appointments appointments_booked_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointments
    ADD CONSTRAINT appointments_booked_by_user_id_foreign FOREIGN KEY (booked_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: appointments appointments_doctor_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointments
    ADD CONSTRAINT appointments_doctor_user_id_foreign FOREIGN KEY (doctor_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: appointments appointments_last_updated_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointments
    ADD CONSTRAINT appointments_last_updated_by_user_id_foreign FOREIGN KEY (last_updated_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: appointments appointments_patient_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointments
    ADD CONSTRAINT appointments_patient_user_id_foreign FOREIGN KEY (patient_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: bill_items bill_items_bill_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_items
    ADD CONSTRAINT bill_items_bill_id_foreign FOREIGN KEY (bill_id) REFERENCES public.bills(id) ON DELETE CASCADE;


--
-- Name: bills bills_created_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bills
    ADD CONSTRAINT bills_created_by_user_id_foreign FOREIGN KEY (created_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bills bills_doctor_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bills
    ADD CONSTRAINT bills_doctor_user_id_foreign FOREIGN KEY (doctor_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bills bills_patient_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bills
    ADD CONSTRAINT bills_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE;


--
-- Name: blocked_time_slots blocked_time_slots_created_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blocked_time_slots
    ADD CONSTRAINT blocked_time_slots_created_by_user_id_foreign FOREIGN KEY (created_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: blocked_time_slots blocked_time_slots_doctor_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blocked_time_slots
    ADD CONSTRAINT blocked_time_slots_doctor_user_id_foreign FOREIGN KEY (doctor_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: doctors doctors_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.doctors
    ADD CONSTRAINT doctors_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: expenses expenses_created_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_created_by_user_id_foreign FOREIGN KEY (created_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: jwt_tokens jwt_tokens_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jwt_tokens
    ADD CONSTRAINT jwt_tokens_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: lab_results lab_results_lab_test_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_results
    ADD CONSTRAINT lab_results_lab_test_id_foreign FOREIGN KEY (lab_test_id) REFERENCES public.lab_tests(id) ON DELETE CASCADE;


--
-- Name: lab_results lab_results_medical_record_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_results
    ADD CONSTRAINT lab_results_medical_record_id_foreign FOREIGN KEY (medical_record_id) REFERENCES public.medical_records(id) ON DELETE SET NULL;


--
-- Name: lab_results lab_results_patient_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_results
    ADD CONSTRAINT lab_results_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE;


--
-- Name: lab_results lab_results_reviewed_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_results
    ADD CONSTRAINT lab_results_reviewed_by_user_id_foreign FOREIGN KEY (reviewed_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: lab_tests lab_tests_patient_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_tests
    ADD CONSTRAINT lab_tests_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE;


--
-- Name: lab_tests lab_tests_requested_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_tests
    ADD CONSTRAINT lab_tests_requested_by_user_id_foreign FOREIGN KEY (requested_by_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: medical_histories medical_histories_patient_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medical_histories
    ADD CONSTRAINT medical_histories_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE;


--
-- Name: medical_histories medical_histories_updated_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medical_histories
    ADD CONSTRAINT medical_histories_updated_by_user_id_foreign FOREIGN KEY (updated_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: medical_images medical_images_medical_record_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medical_images
    ADD CONSTRAINT medical_images_medical_record_id_foreign FOREIGN KEY (medical_record_id) REFERENCES public.medical_records(id) ON DELETE CASCADE;


--
-- Name: medical_images medical_images_uploaded_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medical_images
    ADD CONSTRAINT medical_images_uploaded_by_user_id_foreign FOREIGN KEY (uploaded_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: medical_records medical_records_created_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medical_records
    ADD CONSTRAINT medical_records_created_by_user_id_foreign FOREIGN KEY (created_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: medical_records medical_records_patient_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medical_records
    ADD CONSTRAINT medical_records_patient_user_id_foreign FOREIGN KEY (patient_user_id) REFERENCES public.users(id);


--
-- Name: medical_records medical_records_record_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medical_records
    ADD CONSTRAINT medical_records_record_type_id_foreign FOREIGN KEY (record_type_id) REFERENCES public.record_types(id);


--
-- Name: patient_alerts patient_alerts_patient_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_alerts
    ADD CONSTRAINT patient_alerts_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE;


--
-- Name: patient_files patient_files_patient_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_files
    ADD CONSTRAINT patient_files_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE;


--
-- Name: patient_files patient_files_uploaded_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_files
    ADD CONSTRAINT patient_files_uploaded_by_user_id_foreign FOREIGN KEY (uploaded_by_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: patient_notes patient_notes_doctor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_notes
    ADD CONSTRAINT patient_notes_doctor_id_foreign FOREIGN KEY (doctor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: patient_notes patient_notes_patient_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_notes
    ADD CONSTRAINT patient_notes_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE;


--
-- Name: patients patients_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patients
    ADD CONSTRAINT patients_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: personal_infos personal_infos_patient_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_infos
    ADD CONSTRAINT personal_infos_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE;


--
-- Name: prescriptions prescriptions_doctor_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prescriptions
    ADD CONSTRAINT prescriptions_doctor_user_id_foreign FOREIGN KEY (doctor_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: prescriptions prescriptions_patient_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prescriptions
    ADD CONSTRAINT prescriptions_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE;


--
-- Name: record_attachments record_attachments_medical_record_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.record_attachments
    ADD CONSTRAINT record_attachments_medical_record_id_foreign FOREIGN KEY (medical_record_id) REFERENCES public.medical_records(id) ON DELETE CASCADE;


--
-- Name: record_attachments record_attachments_uploaded_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.record_attachments
    ADD CONSTRAINT record_attachments_uploaded_by_user_id_foreign FOREIGN KEY (uploaded_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: record_versions record_versions_medical_record_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.record_versions
    ADD CONSTRAINT record_versions_medical_record_id_foreign FOREIGN KEY (medical_record_id) REFERENCES public.medical_records(id) ON DELETE CASCADE;


--
-- Name: record_versions record_versions_updated_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.record_versions
    ADD CONSTRAINT record_versions_updated_by_user_id_foreign FOREIGN KEY (updated_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: reminder_analytics reminder_analytics_doctor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminder_analytics
    ADD CONSTRAINT reminder_analytics_doctor_id_foreign FOREIGN KEY (doctor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: reminder_logs reminder_logs_appointment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminder_logs
    ADD CONSTRAINT reminder_logs_appointment_id_foreign FOREIGN KEY (appointment_id) REFERENCES public.appointments(id) ON DELETE CASCADE;


--
-- Name: reminder_logs reminder_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminder_logs
    ADD CONSTRAINT reminder_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: reminder_settings reminder_settings_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminder_settings
    ADD CONSTRAINT reminder_settings_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: role_permission role_permission_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permission
    ADD CONSTRAINT role_permission_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_permission role_permission_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permission
    ADD CONSTRAINT role_permission_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: scanned_scripts scanned_scripts_medical_record_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scanned_scripts
    ADD CONSTRAINT scanned_scripts_medical_record_id_foreign FOREIGN KEY (medical_record_id) REFERENCES public.medical_records(id) ON DELETE CASCADE;


--
-- Name: scanned_scripts scanned_scripts_uploaded_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scanned_scripts
    ADD CONSTRAINT scanned_scripts_uploaded_by_user_id_foreign FOREIGN KEY (uploaded_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: scheduled_reminder_jobs scheduled_reminder_jobs_appointment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scheduled_reminder_jobs
    ADD CONSTRAINT scheduled_reminder_jobs_appointment_id_foreign FOREIGN KEY (appointment_id) REFERENCES public.appointments(id) ON DELETE CASCADE;


--
-- Name: scheduled_reminder_jobs scheduled_reminder_jobs_reminder_log_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scheduled_reminder_jobs
    ADD CONSTRAINT scheduled_reminder_jobs_reminder_log_id_foreign FOREIGN KEY (reminder_log_id) REFERENCES public.reminder_logs(id) ON DELETE CASCADE;


--
-- Name: staff_profiles staff_profiles_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_profiles
    ADD CONSTRAINT staff_profiles_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: stock_items stock_items_created_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_items
    ADD CONSTRAINT stock_items_created_by_user_id_foreign FOREIGN KEY (created_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: stock_transactions stock_transactions_performed_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_transactions
    ADD CONSTRAINT stock_transactions_performed_by_user_id_foreign FOREIGN KEY (performed_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: stock_transactions stock_transactions_stock_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_transactions
    ADD CONSTRAINT stock_transactions_stock_item_id_foreign FOREIGN KEY (stock_item_id) REFERENCES public.stock_items(id) ON DELETE CASCADE;


--
-- Name: timeline_events timeline_events_created_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.timeline_events
    ADD CONSTRAINT timeline_events_created_by_user_id_foreign FOREIGN KEY (created_by_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: timeline_events timeline_events_patient_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.timeline_events
    ADD CONSTRAINT timeline_events_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE;


--
-- Name: treatments treatments_patient_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.treatments
    ADD CONSTRAINT treatments_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE;


--
-- Name: treatments treatments_prescribed_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.treatments
    ADD CONSTRAINT treatments_prescribed_by_user_id_foreign FOREIGN KEY (prescribed_by_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_ai_model user_ai_model_ai_model_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_ai_model
    ADD CONSTRAINT user_ai_model_ai_model_id_foreign FOREIGN KEY (ai_model_id) REFERENCES public.ai_models(id) ON DELETE CASCADE;


--
-- Name: user_ai_model user_ai_model_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_ai_model
    ADD CONSTRAINT user_ai_model_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_role user_role_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_role
    ADD CONSTRAINT user_role_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: user_role user_role_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_role
    ADD CONSTRAINT user_role_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: vital_signs vital_signs_patient_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vital_signs
    ADD CONSTRAINT vital_signs_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE;


--
-- Name: vital_signs vital_signs_recorded_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vital_signs
    ADD CONSTRAINT vital_signs_recorded_by_user_id_foreign FOREIGN KEY (recorded_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

--
-- PostgreSQL database dump
--

-- Dumped from database version 17.4
-- Dumped by pg_dump version 17.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2025_04_24_095643_create_personal_access_tokens_table	1
5	2025_04_24_103501_create_roles_table	1
6	2025_04_24_103614_create_permissions_table	1
7	2025_04_24_103632_create_activity_logs_table	1
8	2025_04_24_111318_create_user_role_table	1
9	2025_04_24_111337_create_role_permission_table	1
10	2025_04_24_111400_create_ai_models_table	1
11	2025_04_24_111417_create_user_ai_model_table	1
12	2025_04_24_134653_update_users_table_add_status_and_phone	1
13	2025_04_24_140108_add_indexes_to_tables_for_performance	1
14	2025_04_28_100520_create_patients_table	1
15	2025_04_28_100541_create_personal_infos_table	1
16	2025_04_28_100622_create_doctors_table	1
17	2025_04_28_100632_create_staff_profiles_table	1
18	2025_05_21_create_password_change_required_column	1
19	2025_05_24_120356_create_jwt_tokens_table	1
20	2025_05_25_113743_ai_analyses	1
21	2025_05_25_120223_create_activity_log_table	1
22	2025_05_25_120224_add_event_column_to_activity_log_table	1
23	2025_05_25_120225_add_batch_uuid_column_to_activity_log_table	1
24	2025_05_29_100553_create_medical_histories_table	1
25	2025_05_29_100602_create_vital_signs_table	1
26	2025_05_29_100611_create_bills_table	1
27	2025_05_29_100641_create_chart_patients_table	1
28	2025_05_29_100650_create_prescriptions_table	1
29	2025_05_29_100700_create_lab_tests_table	1
30	2025_05_29_100715_create_treatments_table	1
31	2025_05_29_100725_create_record_types_table	1
32	2025_05_29_100736_create_medical_records_table	1
33	2025_05_29_100751_create_record_versions_table	1
34	2025_05_29_100802_create_record_attachments_table	1
35	2025_05_29_100814_create_scanned_scripts_table	1
36	2025_05_29_100825_create_medical_images_table	1
37	2025_05_29_100837_create_lab_results_table	1
38	2025_05_29_100859_create_blocked_time_slots_table	1
39	2025_05_29_100908_create_appointments_table	1
40	2025_05_30_214717_create_bill_items_table	1
41	2025_05_30_220001_create_stock_items_table	1
42	2025_05_30_220137_create_stock_transactions_table	1
43	2025_05_30_220327_create_expenses_table	1
44	2025_06_07_123855_create_reminder_settings_table	1
45	2025_06_07_123903_create_reminder_logs_table	1
46	2025_06_07_123911_create_scheduled_reminder_jobs_table	1
47	2025_06_07_123917_create_reminder_analytics_table	1
48	2025_06_10_080501_create_notifications_table	1
49	2025_06_11_121607_create_patient_notes_table	1
50	2025_06_11_121746_create_patient_alerts_table	1
51	2025_06_11_121756_create_timeline_events_table	1
52	2025_06_11_122225_create_patient_files_table	1
53	2025_06_11_125204_eliminate_chart_patients_add_direct_patient_relationships	1
54	2025_06_11_203949_update_timeline_events_enum_add_alert	1
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 54, true);


--
-- PostgreSQL database dump complete
--

