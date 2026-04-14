--
-- PostgreSQL database dump
--

\restrict fv7XplxeJH1UgINiZVLSdb0xpkkM34svaVOKi9pY1260efe0idsy1YDu10BcOiG

-- Dumped from database version 18.2
-- Dumped by pg_dump version 18.2

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
-- Name: platform; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA platform;


ALTER SCHEMA platform OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: activity_logs; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.activity_logs (
    id character(26) NOT NULL,
    org_id character varying(26) NOT NULL,
    actor_id character varying(26),
    actor_name character varying(255),
    actor_role character varying(255),
    action character varying(100) NOT NULL,
    entity_type character varying(100) NOT NULL,
    entity_id character varying(26),
    entity_snapshot jsonb,
    ip_address character varying(45),
    user_agent text,
    occurred_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE platform.activity_logs OWNER TO postgres;

--
-- Name: actor_relationships; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.actor_relationships (
    id character(26) NOT NULL,
    actor_id character(26) NOT NULL,
    related_actor_id character(26) NOT NULL,
    relationship_type character varying(100) NOT NULL,
    source_module character varying(100) NOT NULL,
    source_event character varying(150) NOT NULL,
    direction character varying(20) DEFAULT 'bilateral'::character varying NOT NULL,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    metadata jsonb,
    initiated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    confirmed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.actor_relationships OWNER TO postgres;

--
-- Name: actor_type_assignments; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.actor_type_assignments (
    actor_id character(26) NOT NULL,
    actor_type_id character(26) NOT NULL,
    assigned_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    assigned_by character(26)
);


ALTER TABLE platform.actor_type_assignments OWNER TO postgres;

--
-- Name: actor_types; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.actor_types (
    id character(26) NOT NULL,
    name character varying(100) NOT NULL,
    source character varying(20) DEFAULT 'platform'::character varying NOT NULL,
    module character varying(100),
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.actor_types OWNER TO postgres;

--
-- Name: actors; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.actors (
    id character(26) NOT NULL,
    display_name character varying(255) NOT NULL,
    avatar_url text,
    metadata jsonb,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE platform.actors OWNER TO postgres;

--
-- Name: audit_log_configs; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.audit_log_configs (
    id character(26) NOT NULL,
    module character varying(100) NOT NULL,
    action character varying(150) NOT NULL,
    is_enabled boolean DEFAULT true NOT NULL,
    retention_days integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.audit_log_configs OWNER TO postgres;

--
-- Name: audit_logs; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.audit_logs (
    id character(26) NOT NULL,
    module character varying(100) NOT NULL,
    action character varying(150) NOT NULL,
    actor_id character(26),
    subject_type character varying(100) NOT NULL,
    subject_id character(26) NOT NULL,
    old_values jsonb,
    new_values jsonb,
    metadata jsonb,
    ip_address character varying(45),
    user_agent text,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE platform.audit_logs OWNER TO postgres;

--
-- Name: cache; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE platform.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE platform.cache_locks OWNER TO postgres;

--
-- Name: event_dispatch_log; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.event_dispatch_log (
    id character(26) NOT NULL,
    event_name character varying(200) NOT NULL,
    module character varying(100) NOT NULL,
    payload jsonb NOT NULL,
    actor_id character(26),
    dispatch_mode character varying(20) NOT NULL,
    status character varying(50) DEFAULT 'dispatched'::character varying NOT NULL,
    fired_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE platform.event_dispatch_log OWNER TO postgres;

--
-- Name: event_registry; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.event_registry (
    id character(26) NOT NULL,
    name character varying(200) NOT NULL,
    module character varying(100) NOT NULL,
    description text,
    payload_schema jsonb,
    dispatch_mode character varying(20) DEFAULT 'async'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE platform.event_registry OWNER TO postgres;

--
-- Name: failed_jobs; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE platform.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: platform; Owner: postgres
--

CREATE SEQUENCE platform.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE platform.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: platform; Owner: postgres
--

ALTER SEQUENCE platform.failed_jobs_id_seq OWNED BY platform.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.job_batches (
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


ALTER TABLE platform.job_batches OWNER TO postgres;

--
-- Name: jobs; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE platform.jobs OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: platform; Owner: postgres
--

CREATE SEQUENCE platform.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE platform.jobs_id_seq OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: platform; Owner: postgres
--

ALTER SEQUENCE platform.jobs_id_seq OWNED BY platform.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE platform.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: platform; Owner: postgres
--

CREATE SEQUENCE platform.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE platform.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: platform; Owner: postgres
--

ALTER SEQUENCE platform.migrations_id_seq OWNED BY platform.migrations.id;


--
-- Name: org_delegation_permissions; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.org_delegation_permissions (
    delegation_id character(26) NOT NULL,
    org_permission_def_id character(26) NOT NULL
);


ALTER TABLE platform.org_delegation_permissions OWNER TO postgres;

--
-- Name: org_invitations; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.org_invitations (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    org_role_id character(26) NOT NULL,
    level smallint DEFAULT '0'::smallint NOT NULL,
    email character varying(255) NOT NULL,
    token character varying(64) NOT NULL,
    invited_by character(26) NOT NULL,
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.org_invitations OWNER TO postgres;

--
-- Name: org_memberships; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.org_memberships (
    id character(26) NOT NULL,
    user_id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    org_role_id character(26) NOT NULL,
    level smallint DEFAULT '0'::smallint NOT NULL,
    invited_by character(26),
    status character varying(50) DEFAULT 'invited'::character varying NOT NULL,
    joined_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_level_range CHECK (((level >= 0) AND (level <= 100)))
);


ALTER TABLE platform.org_memberships OWNER TO postgres;

--
-- Name: org_permission_definitions; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.org_permission_definitions (
    id character(26) NOT NULL,
    name character varying(150) NOT NULL,
    group_name character varying(100) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.org_permission_definitions OWNER TO postgres;

--
-- Name: org_permission_requests; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.org_permission_requests (
    id character(26) NOT NULL,
    requesting_org_id character(26) NOT NULL,
    target_org_id character(26) NOT NULL,
    org_role_id character(26) NOT NULL,
    org_permission_def_id character(26) NOT NULL,
    reason text,
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL,
    reviewed_by character(26),
    reviewed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.org_permission_requests OWNER TO postgres;

--
-- Name: org_role_delegations; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.org_role_delegations (
    id character(26) NOT NULL,
    parent_org_id character(26) NOT NULL,
    child_org_id character(26) NOT NULL,
    org_role_id character(26) NOT NULL,
    granted_by character(26) NOT NULL,
    granted_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.org_role_delegations OWNER TO postgres;

--
-- Name: org_role_permissions; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.org_role_permissions (
    org_role_id character(26) NOT NULL,
    org_permission_def_id character(26) NOT NULL
);


ALTER TABLE platform.org_role_permissions OWNER TO postgres;

--
-- Name: org_roles; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.org_roles (
    id character(26) NOT NULL,
    root_org_id character(26) NOT NULL,
    name character varying(100) NOT NULL,
    source character varying(20) DEFAULT 'custom'::character varying NOT NULL,
    default_role_id character(26),
    is_system boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.org_roles OWNER TO postgres;

--
-- Name: org_scope_grant_branches; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.org_scope_grant_branches (
    scope_grant_id character(26) NOT NULL,
    org_id character(26) NOT NULL
);


ALTER TABLE platform.org_scope_grant_branches OWNER TO postgres;

--
-- Name: org_scope_grants; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.org_scope_grants (
    id character(26) NOT NULL,
    membership_id character(26) NOT NULL,
    scope_type character varying(50) NOT NULL,
    granted_by character(26) NOT NULL,
    granted_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.org_scope_grants OWNER TO postgres;

--
-- Name: org_scope_requests; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.org_scope_requests (
    id character(26) NOT NULL,
    membership_id character(26) NOT NULL,
    requested_scope character varying(50) NOT NULL,
    target_org_ids jsonb,
    reason text,
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL,
    reviewed_by character(26),
    reviewed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.org_scope_requests OWNER TO postgres;

--
-- Name: organizations; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.organizations (
    id character(26) NOT NULL,
    actor_id character(26) NOT NULL,
    parent_id character(26),
    root_org_id character(26),
    path platform.ltree NOT NULL,
    depth smallint DEFAULT '0'::smallint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    type character varying(50) DEFAULT 'root'::character varying NOT NULL,
    status character varying(50) DEFAULT 'pending_approval'::character varying NOT NULL,
    settings jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    approved_by character(26),
    approved_at timestamp(0) without time zone,
    rejection_reason text
);


ALTER TABLE platform.organizations OWNER TO postgres;

--
-- Name: personal_access_tokens; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id character(26) NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.personal_access_tokens OWNER TO postgres;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: platform; Owner: postgres
--

CREATE SEQUENCE platform.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE platform.personal_access_tokens_id_seq OWNER TO postgres;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: platform; Owner: postgres
--

ALTER SEQUENCE platform.personal_access_tokens_id_seq OWNED BY platform.personal_access_tokens.id;


--
-- Name: platform_default_role_permissions; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.platform_default_role_permissions (
    default_role_id character(26) NOT NULL,
    org_permission_def_id character(26) CONSTRAINT platform_default_role_permission_org_permission_def_id_not_null NOT NULL
);


ALTER TABLE platform.platform_default_role_permissions OWNER TO postgres;

--
-- Name: platform_default_roles; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.platform_default_roles (
    id character(26) NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.platform_default_roles OWNER TO postgres;

--
-- Name: platform_feature_flags; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.platform_feature_flags (
    id character(26) NOT NULL,
    key character varying(150) NOT NULL,
    value boolean DEFAULT false NOT NULL,
    description text,
    module character varying(100),
    updated_by character(26),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.platform_feature_flags OWNER TO postgres;

--
-- Name: platform_permissions; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.platform_permissions (
    id character(26) NOT NULL,
    name character varying(150) NOT NULL,
    group_name character varying(100) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.platform_permissions OWNER TO postgres;

--
-- Name: platform_role_permissions; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.platform_role_permissions (
    platform_role_id character(26) NOT NULL,
    platform_permission_id character(26) NOT NULL
);


ALTER TABLE platform.platform_role_permissions OWNER TO postgres;

--
-- Name: platform_roles; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.platform_roles (
    id character(26) NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    is_system boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.platform_roles OWNER TO postgres;

--
-- Name: platform_tiers; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.platform_tiers (
    id character(26) NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    is_default boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    sort_order smallint DEFAULT '0'::smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.platform_tiers OWNER TO postgres;

--
-- Name: sessions; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE platform.sessions OWNER TO postgres;

--
-- Name: user_platform_roles; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.user_platform_roles (
    user_id character(26) NOT NULL,
    platform_role_id character(26) NOT NULL,
    granted_by character(26),
    granted_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE platform.user_platform_roles OWNER TO postgres;

--
-- Name: user_social_logins; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.user_social_logins (
    id character(26) NOT NULL,
    user_id character(26) NOT NULL,
    provider character varying(50) NOT NULL,
    provider_id character varying(255) NOT NULL,
    access_token text,
    refresh_token text,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.user_social_logins OWNER TO postgres;

--
-- Name: user_tier_assignments; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.user_tier_assignments (
    id character(26) NOT NULL,
    user_id character(26) NOT NULL,
    tier_id character(26) NOT NULL,
    assigned_by character(26),
    starts_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    expires_at timestamp(0) without time zone,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE platform.user_tier_assignments OWNER TO postgres;

--
-- Name: users; Type: TABLE; Schema: platform; Owner: postgres
--

CREATE TABLE platform.users (
    id character(26) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255),
    remember_token character varying(100),
    two_factor_secret text,
    two_factor_recovery_codes text,
    two_factor_confirmed_at timestamp(0) without time zone,
    actor_id character(26),
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    last_login_at timestamp(0) without time zone,
    last_login_ip character varying(45),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    username character varying(50) NOT NULL
);


ALTER TABLE platform.users OWNER TO postgres;

--
-- Name: failed_jobs id; Type: DEFAULT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.failed_jobs ALTER COLUMN id SET DEFAULT nextval('platform.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.jobs ALTER COLUMN id SET DEFAULT nextval('platform.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.migrations ALTER COLUMN id SET DEFAULT nextval('platform.migrations_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('platform.personal_access_tokens_id_seq'::regclass);


--
-- Data for Name: activity_logs; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.activity_logs (id, org_id, actor_id, actor_name, actor_role, action, entity_type, entity_id, entity_snapshot, ip_address, user_agent, occurred_at, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: actor_relationships; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.actor_relationships (id, actor_id, related_actor_id, relationship_type, source_module, source_event, direction, status, metadata, initiated_at, confirmed_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: actor_type_assignments; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.actor_type_assignments (actor_id, actor_type_id, assigned_at, assigned_by) FROM stdin;
01KKXYVPQ90WV3RWBQHJQ79BQM	01KKXYTRZBP0SAHK2QTCN383A3	2026-03-17 13:13:15	\N
01KKY1ARKP3E1BY0K2GCYQEA34	01KKXYTRZBP0SAHK2QTCN383A3	2026-03-17 13:56:26	\N
01KKZVWF3DH62MK9ZD22CZ2F55	01KKXYTS0JJAYPRQQXWSGMMV2F	2026-03-18 06:59:43	01KKXYVPQ90WV3RWBQHJQ79BQM
01KKZWAR1B6X70FP84T9D47VZM	01KKXYTS0JJAYPRQQXWSGMMV2F	2026-03-18 07:07:31	01KKXYVPQ90WV3RWBQHJQ79BQM
01KKZWC6ZMFF1AWCK0DAR2Y2N4	01KKXYTS0JJAYPRQQXWSGMMV2F	2026-03-18 07:08:19	01KKXYVPQ90WV3RWBQHJQ79BQM
01KKZWP4R72CD7BXVZXK1GA0B1	01KKXYTS0JJAYPRQQXWSGMMV2F	2026-03-18 07:13:44	01KKXYVPQ90WV3RWBQHJQ79BQM
01KKZWVGF1BKBRBF5T36SGNQGY	01KKXYTS0JJAYPRQQXWSGMMV2F	2026-03-18 07:16:40	01KKXYVPQ90WV3RWBQHJQ79BQM
01KKZWVHB5ZYRZ5XV9DDZATR4F	01KKXYTS0JJAYPRQQXWSGMMV2F	2026-03-18 07:16:41	01KKXYVPQ90WV3RWBQHJQ79BQM
01KKZXFFFCRBTCG3ZG76ZSWDR1	01KKXYTS0JJAYPRQQXWSGMMV2F	2026-03-18 07:27:34	01KKXYVPQ90WV3RWBQHJQ79BQM
01KKZZCYRF08A3ANG20KQY6XCP	01KKXYTRZBP0SAHK2QTCN383A3	2026-03-18 08:01:09	\N
01KKZZCZSAA9ZM3JNZ3WE4SV3Z	01KKXYTS0JJAYPRQQXWSGMMV2F	2026-03-18 08:01:10	01KKXYVPQ90WV3RWBQHJQ79BQM
01KP5W23VFZNEZ1P8F7YXPZY60	01KKXYTRZBP0SAHK2QTCN383A3	2026-04-14 11:29:39	\N
\.


--
-- Data for Name: actor_types; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.actor_types (id, name, source, module, description, is_active, created_at, updated_at) FROM stdin;
01KKXYTRZBP0SAHK2QTCN383A3	user	platform	\N	Registered human user with system credentials	t	2026-03-17 13:12:44	2026-03-17 13:12:44
01KKXYTS0JJAYPRQQXWSGMMV2F	organization	platform	\N	A tenant organization or branch	t	2026-03-17 13:12:44	2026-03-17 13:12:44
01KKXYTS0KVMME2HJN1BW3VSWC	ai_agent	platform	\N	An AI agent participating in institutional processes	t	2026-03-17 13:12:44	2026-03-17 13:12:44
01KKXYTS0M8T1WVQRB0GV3EE8T	iot_device	platform	\N	An IoT device or sensor	t	2026-03-17 13:12:44	2026-03-17 13:12:44
01KKXYTS0NYG9TEE9ZWD0KJ0YT	external_system	platform	\N	Third-party or external system integration	t	2026-03-17 13:12:44	2026-03-17 13:12:44
01KKXYTS0NYG9TEE9ZWD0KJ0YV	virtual_entity	platform	\N	A bot, automated process, or virtual participant	t	2026-03-17 13:12:44	2026-03-17 13:12:44
\.


--
-- Data for Name: actors; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.actors (id, display_name, avatar_url, metadata, status, created_at, updated_at, deleted_at) FROM stdin;
01KKXYVPQ90WV3RWBQHJQ79BQM	testuser2	\N	\N	active	2026-03-17 13:13:15	2026-03-17 13:13:15	\N
01KKY1ARKP3E1BY0K2GCYQEA34	supportagent1	\N	\N	active	2026-03-17 13:56:25	2026-03-17 13:56:25	\N
01KKZVWF3DH62MK9ZD22CZ2F55	Nexora Health International	\N	\N	active	2026-03-18 06:59:43	2026-03-18 06:59:48	\N
01KKZWAR1B6X70FP84T9D47VZM	Nexora Health Ltd	\N	\N	active	2026-03-18 07:07:31	2026-03-18 07:07:31	\N
01KKZWC6ZMFF1AWCK0DAR2Y2N4	Nexora Health Ltd	\N	\N	active	2026-03-18 07:08:19	2026-03-18 07:08:19	\N
01KKZWP4R72CD7BXVZXK1GA0B1	Nexora Health Ltd	\N	\N	active	2026-03-18 07:13:44	2026-03-18 07:13:44	\N
01KKZWVHB5ZYRZ5XV9DDZATR4F	Nairobi Branch	\N	\N	active	2026-03-18 07:16:41	2026-03-18 07:16:41	\N
01KKZWVGF1BKBRBF5T36SGNQGY	Nexora Health International	\N	\N	active	2026-03-18 07:16:40	2026-03-18 07:16:44	\N
01KKZXFFFCRBTCG3ZG76ZSWDR1	Fix Test Org	\N	\N	active	2026-03-18 07:27:34	2026-03-18 07:27:34	\N
01KKZZCYRF08A3ANG20KQY6XCP	eventtest1	\N	\N	active	2026-03-18 08:01:09	2026-03-18 08:01:09	\N
01KKZZCZSAA9ZM3JNZ3WE4SV3Z	Event Test Org	\N	\N	active	2026-03-18 08:01:10	2026-03-18 08:01:10	\N
01KP5W23VFZNEZ1P8F7YXPZY60	Elia	\N	\N	active	2026-04-14 11:29:38	2026-04-14 11:29:38	\N
01KP5XBQMA2P4V7VDDJ4EFTVFJ	Super Admin	\N	\N	active	2026-04-14 11:52:22	2026-04-14 11:52:22	\N
\.


--
-- Data for Name: audit_log_configs; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.audit_log_configs (id, module, action, is_enabled, retention_days, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: audit_logs; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.audit_logs (id, module, action, actor_id, subject_type, subject_id, old_values, new_values, metadata, ip_address, user_agent, created_at) FROM stdin;
01KKZZCZA99E87FE47CRW3KKPN	platform	user.registered	01KKZZCYRF08A3ANG20KQY6XCP	User	01KKZZCZ2W9CMX2J40NK658G9G	\N	{"email": "eventtest1@nexora.dev", "username": "eventtest1"}	\N	127.0.0.1	GuzzleHttp/7	2026-03-18 08:01:09
01KKZZD08F1A8QSBQ9YWSPJM4K	platform	org.approved	01KKXYVPQ90WV3RWBQHJQ79BQM	Organization	01KKZZCZSP8JPJF90YEZ42KTRQ	{"status": "pending_approval", "approved_at": null, "approved_by": null}	{"status": "active", "approved_at": "2026-03-18T08:01:10.000000Z", "approved_by": "01KKXYVPYCP7W43V8MED2CGVY7"}	\N	127.0.0.1	GuzzleHttp/7	2026-03-18 08:01:10
01KP5W24JV8S2NSQ6QR3PP9D1X	platform	user.registered	01KP5W23VFZNEZ1P8F7YXPZY60	User	01KP5W249DN4F9N24ZX2AMXGX6	\N	{"email": "elia@elia.elia", "username": "Elia"}	\N	127.0.0.1	Dart/3.10 (dart:io)	2026-04-14 11:29:39
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: event_dispatch_log; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.event_dispatch_log (id, event_name, module, payload, actor_id, dispatch_mode, status, fired_at, created_at) FROM stdin;
01KKZZCZ8KHVTQRG5G4T84WDD2	platform.user.registered	platform	{"email": "eventtest1@nexora.dev", "user_id": "01KKZZCZ2W9CMX2J40NK658G9G", "actor_id": "01KKZZCYRF08A3ANG20KQY6XCP", "username": "eventtest1"}	01KKZZCYRF08A3ANG20KQY6XCP	sync	dispatched	2026-03-18 08:01:09	2026-03-18 08:01:09
01KKZZD08B6PS8KSVDNCZH9HDG	platform.org.approved	platform	{"org_id": "01KKZZCZSP8JPJF90YEZ42KTRQ", "approved_at": "2026-03-18T08:01:10.922981Z", "approved_by": "01KKXYVPYCP7W43V8MED2CGVY7"}	01KKXYVPQ90WV3RWBQHJQ79BQM	sync	dispatched	2026-03-18 08:01:10	2026-03-18 08:01:10
01KP5W24G0C9YPPN5QJ6E96797	platform.user.registered	platform	{"email": "elia@elia.elia", "user_id": "01KP5W249DN4F9N24ZX2AMXGX6", "actor_id": "01KP5W23VFZNEZ1P8F7YXPZY60", "username": "Elia"}	01KP5W23VFZNEZ1P8F7YXPZY60	sync	dispatched	2026-04-14 11:29:39	2026-04-14 11:29:39
\.


--
-- Data for Name: event_registry; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.event_registry (id, name, module, description, payload_schema, dispatch_mode, is_active, created_at) FROM stdin;
01KKZZCH01M2B097CEWACX7QHY	platform.user.registered	platform	\N	\N	sync	t	2026-03-18 08:00:55
01KKZZCH1Q4DBT1PDG4801YVGM	platform.org.created	platform	\N	\N	sync	t	2026-03-18 08:00:55
01KKZZCH1R5YYCGB0ZD4654K2M	platform.org.approved	platform	\N	\N	sync	t	2026-03-18 08:00:55
01KKZZCH1SGHFC748KEEM6A4YN	platform.org.rejected	platform	\N	\N	sync	t	2026-03-18 08:00:55
01KKZZCH1TT1TZFYSH7GWBJYDY	platform.org.suspended	platform	\N	\N	sync	t	2026-03-18 08:00:55
01KKZZCH1V11797J8WABZQY6RM	platform.member.invited	platform	\N	\N	sync	t	2026-03-18 08:00:55
01KKZZCH1WF84XKJX04NQZVAEJ	platform.member.joined	platform	\N	\N	sync	t	2026-03-18 08:00:55
01KKZZCH1XHMW98MX10NVQQYVC	platform.actor.relationship.created	platform	\N	\N	sync	t	2026-03-18 08:00:55
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000001_create_cache_table	1
2	0001_01_01_000002_create_jobs_table	1
3	2026_01_001_001_enable_extensions	1
4	2026_01_001_002_create_users_table	1
5	2026_01_001_003_create_user_social_logins_table	1
6	2026_01_001_004_create_actors_table	1
7	2026_01_001_005_create_actor_types_table	1
8	2026_01_001_006_add_actor_fk_to_users_table	1
9	2026_01_001_007_create_actor_type_assignments_table	1
10	2026_01_001_008_create_actor_relationships_table	1
11	2026_01_001_009_create_organizations_table	1
12	2026_01_001_009b_add_org_self_referential_fks	1
13	2026_01_001_010_create_platform_roles_table	1
14	2026_01_001_011_create_platform_permissions_table	1
15	2026_01_001_012_create_platform_role_permissions_table	1
16	2026_01_001_013_create_user_platform_roles_table	1
17	2026_01_001_014_create_org_permission_definitions_table	1
18	2026_01_001_015_create_platform_default_roles_table	1
19	2026_01_001_016_create_platform_default_role_permissions_table	1
20	2026_01_001_017_create_org_roles_table	1
21	2026_01_001_018_create_org_role_permissions_table	1
22	2026_01_001_019_create_org_role_delegations_table	1
23	2026_01_001_020_create_org_delegation_permissions_table	1
24	2026_01_001_021_create_org_permission_requests_table	1
25	2026_01_001_022_create_org_memberships_table	1
26	2026_01_001_023_create_org_scope_grants_table	1
27	2026_01_001_024_create_org_scope_grant_branches_table	1
28	2026_01_001_025_create_org_scope_requests_table	1
29	2026_01_001_026_create_platform_tiers_table	1
30	2026_01_001_027_create_user_tier_assignments_table	1
31	2026_01_001_028_create_platform_feature_flags_table	1
32	2026_01_001_029_create_event_registry_table	1
33	2026_01_001_030_create_event_dispatch_log_table	1
34	2026_01_001_031_create_audit_logs_table	1
35	2026_01_001_032_create_audit_log_configs_table	1
36	2026_01_002_001_add_username_to_users_table	1
37	2026_03_17_131039_create_personal_access_tokens_table	1
38	2026_01_003_001_add_approval_columns_to_organizations	2
39	2026_01_004_001_add_invite_token_to_org_memberships	3
40	2026_01_004_001_create_org_invitations_table	3
41	2026_01_004_001_seed_org_permission_definitions	3
42	2026_01_004_002_seed_platform_default_roles	3
43	2026_01_005_001_drop_dead_columns_from_org_memberships	4
44	2026_02_001_001_enable_finance_schema	5
45	2026_02_001_002_create_subscription_plans_table	5
46	2026_02_001_003_create_subscription_plan_limits_table	5
47	2026_02_001_004_create_org_subscriptions_table	5
48	2026_02_001_005_create_invoices_table	5
49	2026_02_001_006_create_invoice_line_items_table	5
50	2026_02_001_007_create_payments_table	5
51	2026_02_001_008_create_credit_accounts_table	5
52	2026_02_001_009_create_credit_transactions_table	5
53	2026_02_001_010_create_commission_configs_table	5
54	2026_02_001_011_create_commission_records_table	5
55	2026_02_001_012_create_org_pricing_tiers_table	5
56	2026_02_001_013_create_promotions_table	5
57	2026_02_001_014_create_promotion_usages_table	5
58	2026_03_001_001_enable_inventory_schema	6
59	2026_03_001_002_create_warehouses_table	6
60	2026_03_001_003_create_inventory_batches_table	6
61	2026_03_001_004_create_stock_movements_table	6
62	2026_03_001_005_create_stock_alerts_table	6
63	2026_03_001_006_create_stock_reservations_table	6
64	2026_04_001_001_enable_commerce_schema	7
65	2026_04_001_002_create_products_table	7
66	2026_04_001_003_create_product_variants_table	7
67	2026_04_001_004_create_product_attributes_table	7
68	2026_04_001_005_create_product_bundles_table	7
69	2026_04_001_006_create_shipping_rates_table	7
70	2026_04_001_007_create_baskets_table	7
71	2026_04_001_008_create_basket_items_table	7
72	2026_04_001_009_create_orders_table	7
73	2026_04_001_010_create_order_items_table	7
74	2026_04_001_011_create_order_fulfillments_table	7
75	2026_04_001_012_create_order_returns_table	7
76	2026_05_001_001_enable_communications_schema	8
77	2026_05_001_002_create_direct_conversations_table	8
78	2026_05_001_003_create_direct_messages_table	8
79	2026_05_001_004_create_groups_table	8
80	2026_05_001_005_create_group_participants_table	8
81	2026_05_001_006_create_group_messages_table	8
82	2026_05_001_007_create_broadcasts_table	8
83	2026_05_001_008_create_broadcast_recipients_table	8
84	2026_05_001_009_create_broadcast_messages_table	8
85	2026_05_001_010_create_communities_table	8
86	2026_05_001_011_create_community_members_table	8
87	2026_05_001_012_create_community_groups_table	8
88	2026_05_001_013_create_message_attachments_table	8
89	2026_05_001_014_create_message_reactions_table	8
90	2026_05_001_015_create_message_receipts_table	8
91	2026_05_001_016_create_actor_presence_table	8
92	2026_06_001_001_enable_notifications_schema	9
93	2026_06_001_002_create_device_tokens_table	9
94	2026_06_001_003_create_notifications_table	9
95	2026_06_001_004_create_notification_preferences_table	9
96	2026_06_001_005_create_workflow_definitions_table	9
97	2026_06_001_006_create_workflow_runs_table	9
98	2026_06_001_007_create_workflow_step_logs_table	9
99	2026_07_001_001_enable_pharma_marketing_schema	10
100	2026_07_001_002_create_pm_customers_table	10
101	2026_07_001_003_create_pm_customer_contacts_table	10
102	2026_07_001_004_create_pm_field_visits_table	10
103	2026_07_001_005_create_pm_visit_attachments_table	10
104	2026_07_001_006_create_pm_visit_products_table	10
105	2026_07_001_007_create_pm_weekly_plans_table	10
106	2026_07_001_008_create_pm_weekly_plan_items_table	10
107	2026_07_001_009_create_pm_product_updates_table	10
108	2026_07_001_010_create_pm_product_update_deliveries_table	10
109	2026_07_001_011_create_pm_daily_reports_table	10
110	2026_08_001_001_enable_logistics_schema	11
111	2026_08_001_002_create_lg_vehicles_table	11
112	2026_08_001_003_create_lg_drivers_table	11
113	2026_08_001_004_create_lg_delivery_zones_table	11
114	2026_08_001_005_create_lg_delivery_rates_table	11
115	2026_08_001_006_create_lg_delivery_runs_table	11
116	2026_08_001_007_create_lg_delivery_stops_table	11
117	2026_08_001_008_create_lg_stop_status_logs_table	11
118	2026_08_001_009_create_lg_delivery_proofs_table	11
119	2026_08_001_010_create_lg_courier_accounts_table	11
120	2026_08_001_011_create_lg_courier_shipments_table	11
121	2026_01_006_002_seed_pharma_permission_definitions	12
122	2026_03_31_141252_create_sessions_table	12
123	2026_04_05_225911_create_activity_logs_table	12
\.


--
-- Data for Name: org_delegation_permissions; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.org_delegation_permissions (delegation_id, org_permission_def_id) FROM stdin;
\.


--
-- Data for Name: org_invitations; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.org_invitations (id, org_id, org_role_id, level, email, token, invited_by, status, expires_at, created_at, updated_at) FROM stdin;
01KKZWVJNWECQNAHYS2W212VSA	01KKZWVGFGG6R1H1J5S43CW0MF	01KKZWVGFMFYKZ9NGWE52PZ2YF	50	support@nexora.dev	20IKhVlr05n0BBnJhiHTXqNxc39jkfWitzG0J1clJ9HO3tEZAtHEaGsHp6I0tZmt	01KKXYVPYCP7W43V8MED2CGVY7	accepted	2026-03-25 07:16:42	2026-03-18 07:16:42	2026-03-18 07:16:43
\.


--
-- Data for Name: org_memberships; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.org_memberships (id, user_id, org_id, org_role_id, level, invited_by, status, joined_at, created_at, updated_at) FROM stdin;
01KKZVWFC58P7VF8TYCBY974BV	01KKXYVPYCP7W43V8MED2CGVY7	01KKZVWF94VEQWQH7ZETYRGQ4D	01KKZVWFB4YA7W40FKE5Q6VVNW	100	01KKXYVPYCP7W43V8MED2CGVY7	active	2026-03-18 06:59:43	2026-03-18 06:59:43	2026-03-18 06:59:43
01KKZWAR1YR226F3ZM7ZM34JRV	01KKXYVPYCP7W43V8MED2CGVY7	01KKZWAR1R3SJRQY6SNE4G9RMZ	01KKZWAR1WX2KM9DAVV19GZ6G6	100	01KKXYVPYCP7W43V8MED2CGVY7	active	2026-03-18 07:07:31	2026-03-18 07:07:31	2026-03-18 07:07:31
01KKZWC70620EJ852E2NH9KP4E	01KKXYVPYCP7W43V8MED2CGVY7	01KKZWC7011V5NTBFF1XFTH1D4	01KKZWC704VYQJ7MZT5NWM4TYK	100	01KKXYVPYCP7W43V8MED2CGVY7	active	2026-03-18 07:08:19	2026-03-18 07:08:19	2026-03-18 07:08:19
01KKZWP4RTNWZN029C2XNXWH7H	01KKXYVPYCP7W43V8MED2CGVY7	01KKZWP4RNF5TRAFGYY88Q9R6D	01KKZWP4RR9M1727TMX6N86QYR	100	01KKXYVPYCP7W43V8MED2CGVY7	active	2026-03-18 07:13:44	2026-03-18 07:13:44	2026-03-18 07:13:44
01KKZWVGFP53VFKTZ4XMHT6HSA	01KKXYVPYCP7W43V8MED2CGVY7	01KKZWVGFGG6R1H1J5S43CW0MF	01KKZWVGFMFYKZ9NGWE52PZ2YF	100	01KKXYVPYCP7W43V8MED2CGVY7	active	2026-03-18 07:16:40	2026-03-18 07:16:40	2026-03-18 07:16:40
01KKZWVK3AZDYK5P8KWXQZQY4R	01KKY1ARTMV5DNN8C9W4H33FJM	01KKZWVGFGG6R1H1J5S43CW0MF	01KKZWVGFMFYKZ9NGWE52PZ2YF	50	01KKXYVPYCP7W43V8MED2CGVY7	active	2026-03-18 07:16:43	2026-03-18 07:16:43	2026-03-18 07:16:43
01KKZXFFFY3J7Z5S8NKC9JNZRM	01KKXYVPYCP7W43V8MED2CGVY7	01KKZXFFFRF4J0V30J6Q7Y6J63	01KKZXFFFVSQFHJCF61SG47KYZ	100	01KKXYVPYCP7W43V8MED2CGVY7	active	2026-03-18 07:27:34	2026-03-18 07:27:34	2026-03-18 07:27:34
01KKZZCZSW2JB4NT7VR9FGVREH	01KKXYVPYCP7W43V8MED2CGVY7	01KKZZCZSP8JPJF90YEZ42KTRQ	01KKZZCZSTES8M2BHY2H17GSCT	100	01KKXYVPYCP7W43V8MED2CGVY7	active	2026-03-18 08:01:10	2026-03-18 08:01:10	2026-03-18 08:01:10
\.


--
-- Data for Name: org_permission_definitions; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.org_permission_definitions (id, name, group_name, description, is_active, created_at, updated_at) FROM stdin;
01KKY76V96F6K3BZXM2JFJ03R5	members.view	members	\N	t	2026-03-17 15:39:08	2026-03-17 15:39:08
01KKY76V9ZSZVMVEQBS7QEJQ11	members.invite	members	\N	t	2026-03-17 15:39:08	2026-03-17 15:39:08
01KKY76VA0NJ1C908NZ4V68T9K	members.remove	members	\N	t	2026-03-17 15:39:08	2026-03-17 15:39:08
01KKY76VA0NJ1C908NZ4V68T9M	members.update	members	\N	t	2026-03-17 15:39:08	2026-03-17 15:39:08
01KKY76VA7PQF9FV85H6Y5QX20	roles.view	roles	\N	t	2026-03-17 15:39:08	2026-03-17 15:39:08
01KKY76VA8AP87YYREK9T932XZ	roles.manage	roles	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VA8AP87YYREK9T932Y0	branches.view	branches	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VA9PR6FM0R6BSXZ2MF9	branches.create	branches	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VA9PR6FM0R6BSXZ2MFA	branches.update	branches	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAAV4JFNKXTD55Z0KTJ	org.settings.view	org_settings	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAB6K7R5Z1P31XB6S20	org.settings.update	org_settings	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VACZF99TMTPQJ6CRYNX	delegations.manage	delegations	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAD1DAJFWQXA8F0H3FC	orders.view	orders	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAD1DAJFWQXA8F0H3FD	orders.create	orders	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAE7RZH8CA13Y406CCQ	orders.approve	orders	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAE7RZH8CA13Y406CCR	orders.cancel	orders	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAFQS2PVDKA1ZG8QKGE	inventory.view	inventory	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAFQS2PVDKA1ZG8QKGF	inventory.create	inventory	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAGH1HHJB9TY6CW72DA	inventory.update	inventory	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAGH1HHJB9TY6CW72DB	inventory.delete	inventory	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAGH1HHJB9TY6CW72DC	invoices.view	finance	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAHG04JH5NBZMP0BVP8	invoices.manage	finance	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAHG04JH5NBZMP0BVP9	payments.view	finance	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAHG04JH5NBZMP0BVPA	payments.manage	finance	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAJRT81KR0EVWR02KCS	conversations.view	communications	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VAJRT81KR0EVWR02KCT	conversations.create	communications	\N	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KP5VK6E8YZZEEZ7MXFPF13NV	products.view	products	\N	t	2026-04-14 11:21:29	2026-04-14 11:21:29
01KP5VK6VW2E76ZK4FVV87PSFF	products.create	products	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6VW2E76ZK4FVV87PSFG	products.update	products	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6VXB9WM61B4NF0088PN	products.delete	products	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6VXB9WM61B4NF0088PP	categories.view	categories	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6VYBH1SWSNMVY4WTVWC	categories.create	categories	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6VYBH1SWSNMVY4WTVWD	categories.update	categories	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6VZEAM3MFZHTP0TFQZE	categories.delete	categories	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6VZEAM3MFZHTP0TFQZF	customers.view	customers	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6VZEAM3MFZHTP0TFQZG	customers.create	customers	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W0JXWTKW2SFYNPY3NP	customers.update	customers	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W0JXWTKW2SFYNPY3NQ	customers.delete	customers	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W0JXWTKW2SFYNPY3NR	officers.view	officers	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W15Y9359JVTKVA3R8C	officers.create	officers	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W15Y9359JVTKVA3R8D	officers.update	officers	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W2S7M9W83VXFTY3G4Y	officers.delete	officers	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W2S7M9W83VXFTY3G4Z	visits.view	visits	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W3MRWJT33RAQZ1VDAS	visits.create	visits	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W3MRWJT33RAQZ1VDAT	visits.review	visits	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W4HFF94TCV8F4J5PME	visits.accept	visits	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W4HFF94TCV8F4J5PMF	visits.flag	visits	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W4HFF94TCV8F4J5PMG	reports.view	reports	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W5W78JNFX19PEXQPE8	reports.create	reports	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W5W78JNFX19PEXQPE9	reports.accept	reports	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W6B2Q2DFP7EW6SKAGQ	reports.deny	reports	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W6B2Q2DFP7EW6SKAGR	reports.export	reports	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W7CSWEVNX7TMXWMAP3	weeklyplans.view	weekly_plans	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W7CSWEVNX7TMXWMAP4	weeklyplans.create	weekly_plans	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W8CPDVM1VB4Z7TS4YV	weeklyplans.update	weekly_plans	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W8CPDVM1VB4Z7TS4YW	weeklyplans.accept	weekly_plans	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
01KP5VK6W9XFVM2M3ZMWANMVHV	weeklyplans.deny	weekly_plans	\N	t	2026-04-14 11:21:30	2026-04-14 11:21:30
\.


--
-- Data for Name: org_permission_requests; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.org_permission_requests (id, requesting_org_id, target_org_id, org_role_id, org_permission_def_id, reason, status, reviewed_by, reviewed_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: org_role_delegations; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.org_role_delegations (id, parent_org_id, child_org_id, org_role_id, granted_by, granted_at, status, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: org_role_permissions; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.org_role_permissions (org_role_id, org_permission_def_id) FROM stdin;
\.


--
-- Data for Name: org_roles; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.org_roles (id, root_org_id, name, source, default_role_id, is_system, created_at, updated_at) FROM stdin;
01KKZVWFB4YA7W40FKE5Q6VVNW	01KKZVWF94VEQWQH7ZETYRGQ4D	Owner	custom	\N	t	2026-03-18 06:59:43	2026-03-18 06:59:43
01KKZWAR1WX2KM9DAVV19GZ6G6	01KKZWAR1R3SJRQY6SNE4G9RMZ	Owner	custom	\N	t	2026-03-18 07:07:31	2026-03-18 07:07:31
01KKZWC704VYQJ7MZT5NWM4TYK	01KKZWC7011V5NTBFF1XFTH1D4	Owner	custom	\N	t	2026-03-18 07:08:19	2026-03-18 07:08:19
01KKZWP4RR9M1727TMX6N86QYR	01KKZWP4RNF5TRAFGYY88Q9R6D	Owner	custom	\N	t	2026-03-18 07:13:44	2026-03-18 07:13:44
01KKZWVGFMFYKZ9NGWE52PZ2YF	01KKZWVGFGG6R1H1J5S43CW0MF	Owner	custom	\N	t	2026-03-18 07:16:40	2026-03-18 07:16:40
01KKZXFFFVSQFHJCF61SG47KYZ	01KKZXFFFRF4J0V30J6Q7Y6J63	Owner	custom	\N	t	2026-03-18 07:27:34	2026-03-18 07:27:34
01KKZZCZSTES8M2BHY2H17GSCT	01KKZZCZSP8JPJF90YEZ42KTRQ	Owner	custom	\N	t	2026-03-18 08:01:10	2026-03-18 08:01:10
\.


--
-- Data for Name: org_scope_grant_branches; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.org_scope_grant_branches (scope_grant_id, org_id) FROM stdin;
\.


--
-- Data for Name: org_scope_grants; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.org_scope_grants (id, membership_id, scope_type, granted_by, granted_at, status, expires_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: org_scope_requests; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.org_scope_requests (id, membership_id, requested_scope, target_org_ids, reason, status, reviewed_by, reviewed_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: organizations; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.organizations (id, actor_id, parent_id, root_org_id, path, depth, name, slug, type, status, settings, created_at, updated_at, deleted_at, approved_by, approved_at, rejection_reason) FROM stdin;
01KKZVWF94VEQWQH7ZETYRGQ4D	01KKZVWF3DH62MK9ZD22CZ2F55	\N	01KKZVWF94VEQWQH7ZETYRGQ4D	01kkzvwf3mcnntj9qg4z6yfxk0	0	Nexora Health International	nexora-health-ltd	root	active	\N	2026-03-18 06:59:43	2026-03-18 06:59:48	\N	\N	\N	\N
01KKZWAR1R3SJRQY6SNE4G9RMZ	01KKZWAR1B6X70FP84T9D47VZM	\N	01KKZWAR1R3SJRQY6SNE4G9RMZ	01kkzwar1jcd6sxyf3m7t17bnd	0	Nexora Health Ltd	nexora-health-ltd-1	root	active	\N	2026-03-18 07:07:31	2026-03-18 07:07:31	\N	\N	\N	\N
01KKZWC7011V5NTBFF1XFTH1D4	01KKZWC6ZMFF1AWCK0DAR2Y2N4	\N	01KKZWC7011V5NTBFF1XFTH1D4	01kkzwc6zv763af6x790fanwy4	0	Nexora Health Ltd	nexora-health-ltd-2	root	active	\N	2026-03-18 07:08:19	2026-03-18 07:08:19	\N	\N	\N	\N
01KKZWP4RNF5TRAFGYY88Q9R6D	01KKZWP4R72CD7BXVZXK1GA0B1	\N	01KKZWP4RNF5TRAFGYY88Q9R6D	01kkzwp4rez9f8nz48944p3tn4	0	Nexora Health Ltd	nexora-health-ltd-3	root	active	\N	2026-03-18 07:13:44	2026-03-18 07:13:45	\N	\N	\N	\N
01KKZWVHBFND2E87FKPVB7NBH8	01KKZWVHB5ZYRZ5XV9DDZATR4F	01KKZWVGFGG6R1H1J5S43CW0MF	01KKZWVGFGG6R1H1J5S43CW0MF	01kkzwvgf8481zy29fq8t7yerv.01kkzwvhbeva1ca068177aa4kn	1	Nairobi Branch	nairobi-branch	branch	active	\N	2026-03-18 07:16:41	2026-03-18 07:16:41	\N	\N	\N	\N
01KKZWVGFGG6R1H1J5S43CW0MF	01KKZWVGF1BKBRBF5T36SGNQGY	\N	01KKZWVGFGG6R1H1J5S43CW0MF	01kkzwvgf8481zy29fq8t7yerv	0	Nexora Health International	nexora-health-ltd-4	root	active	\N	2026-03-18 07:16:40	2026-03-18 07:16:44	\N	\N	\N	\N
01KKZXFFFRF4J0V30J6Q7Y6J63	01KKZXFFFCRBTCG3ZG76ZSWDR1	\N	01KKZXFFFRF4J0V30J6Q7Y6J63	01kkzxfffk59vhrattykrn9jt6	0	Fix Test Org	fix-test-org	root	active	\N	2026-03-18 07:27:34	2026-03-18 07:27:35	\N	01KKXYVPYCP7W43V8MED2CGVY7	2026-03-18 07:27:35	\N
01KKZZCZSP8JPJF90YEZ42KTRQ	01KKZZCZSAA9ZM3JNZ3WE4SV3Z	\N	01KKZZCZSP8JPJF90YEZ42KTRQ	01kkzzczsj097rn6zvewnsvhya	0	Event Test Org	event-test-org	root	active	\N	2026-03-18 08:01:10	2026-03-18 08:01:10	\N	01KKXYVPYCP7W43V8MED2CGVY7	2026-03-18 08:01:10	\N
\.


--
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) FROM stdin;
1	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	api	688bcb6e21f6df49696f2e2c4bc643e0cf0d28062b6e9cf8411103f70df37836	["*"]	\N	\N	2026-03-17 13:13:16	2026-03-17 13:13:16
19	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	finance-test	b085948a5eb8937bbb70d5cb44fabb55fe51ac1573a135efa413f26f71f5833b	["*"]	2026-03-18 09:02:11	\N	2026-03-18 09:02:06	2026-03-18 09:02:11
13	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	org-test3	f9b1678f392050b399486db37a62005d396dfeb15933ec827aa268ebe8bec376	["*"]	2026-03-18 07:13:46	\N	2026-03-18 07:13:44	2026-03-18 07:13:46
26	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	commerce-test	31d4bea8fe24254ae480cc8bc96edc413e2b08d4e04c21e0c6af2cef4f6cdb13	["*"]	2026-03-18 10:00:54	\N	2026-03-18 10:00:51	2026-03-18 10:00:54
23	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	inv-test	db3f8425507298d570f51229837e0076f92a3fac22ef33355e4296a9fd67c946	["*"]	2026-03-18 09:36:43	\N	2026-03-18 09:36:39	2026-03-18 09:36:43
15	Modules\\Platform\\Models\\User	01KKY1ARTMV5DNN8C9W4H33FJM	inv3	79efbb9740bfd992f088e08f03b2ee1846a518fdf3ca3b4fa18f453df6011709	["*"]	2026-03-18 07:16:43	\N	2026-03-18 07:16:42	2026-03-18 07:16:43
4	Modules\\Platform\\Models\\User	01KKY1ARTMV5DNN8C9W4H33FJM	api	71c8f5b35320276639382b7c6bb6e2453fa76b720681da1737c1f010f56eb481	["*"]	\N	\N	2026-03-17 13:56:26	2026-03-17 13:56:26
3	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	tinker-test	674d5f4348b352294b8194655112be57110b5e1819152ae7017796a28283d14f	["*"]	2026-03-17 13:56:32	\N	2026-03-17 13:54:42	2026-03-17 13:56:32
5	Modules\\Platform\\Models\\User	01KKY1ARTMV5DNN8C9W4H33FJM	tinker-support	2b2ecc731fa82ca9730113414415446ce68cd0d86d5592c7681dbcdc1d5dd007	["*"]	2026-03-17 13:56:56	\N	2026-03-17 13:56:39	2026-03-17 13:56:56
6	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	org-test	f424c1faff70bcd3d82cb4ee8591d2156d26eedac70eec392f5b6e598c3a7324	["*"]	2026-03-17 15:50:33	\N	2026-03-17 15:50:32	2026-03-17 15:50:33
7	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	org-test	f65abc71588f43fdfdcbb60f281ea3c3764bc66a1f2bfeb7bbfabadbd69225df	["*"]	2026-03-18 06:54:26	\N	2026-03-18 06:54:11	2026-03-18 06:54:26
8	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	org-test	fbe8a04b8fc2e15da1891515b14bfa9490ed7c4cadc5b558a134f277c8b5502d	["*"]	2026-03-18 06:57:08	\N	2026-03-18 06:57:08	2026-03-18 06:57:08
14	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	org-test3	cd89ed17341fc232abdb38f02b2983ae767b03a841d162aff75652059e63ed9b	["*"]	2026-03-18 07:16:44	\N	2026-03-18 07:16:40	2026-03-18 07:16:44
16	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	fix-test	339b00e318434a472ce0d6b277a9efc5cf78c4fd102efc7c893e95beaec78dad	["*"]	2026-03-18 07:27:35	\N	2026-03-18 07:27:34	2026-03-18 07:27:35
10	Modules\\Platform\\Models\\User	01KKY1ARTMV5DNN8C9W4H33FJM	invite-test2	edcb137a87c9ab063c3adcdbdd244aa357d59f203adf56820aa1285f1dd5eccd	["*"]	\N	\N	2026-03-18 06:59:47	2026-03-18 06:59:47
9	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	org-test2	75e4ba326c2c9aec5b41639c16499c697a9ff5179f793439dbc2fdc048ffd88c	["*"]	2026-03-18 06:59:48	\N	2026-03-18 06:59:42	2026-03-18 06:59:48
11	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	org-test3	3c567c09747325550bd35e19e35bc102d0fb8756fdf234f10a1f9d61398741e4	["*"]	2026-03-18 07:07:32	\N	2026-03-18 07:07:30	2026-03-18 07:07:32
18	Modules\\Platform\\Models\\User	01KKZZCZ2W9CMX2J40NK658G9G	api	a48a28522de735ae328e293332d1759c0c8e5135c2cd4d0f9acee67484b84865	["*"]	\N	\N	2026-03-18 08:01:09	2026-03-18 08:01:09
12	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	org-test3	9dcf98f23d09340164427b25010ff20d3eb549d31a23f058c8fc305cf53c65c1	["*"]	2026-03-18 07:08:20	\N	2026-03-18 07:08:18	2026-03-18 07:08:20
28	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	commerce-test	49b0a217cb3fa84b98a97e4a053f1d24e3e1875485bff20703d92efe3e2daf66	["*"]	2026-03-18 11:17:58	\N	2026-03-18 11:17:49	2026-03-18 11:17:58
20	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	finance-test	9311cb1c661069ff7890d2655266e6a575de0cf089406e0a26c7269d5efcc34b	["*"]	2026-03-18 09:03:06	\N	2026-03-18 09:03:01	2026-03-18 09:03:06
17	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	1d-test	e1152c3348a9371d0c34b327f90ef1083bdf65859a675fc666de491370b0f4d1	["*"]	2026-03-18 08:01:12	\N	2026-03-18 08:01:08	2026-03-18 08:01:12
24	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	inv-test	7ba07cc28d48477b93fbce6ba5c368f98814df4cad9a200796a451fac82732e2	["*"]	2026-03-18 09:37:14	\N	2026-03-18 09:37:11	2026-03-18 09:37:14
21	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	sub-test	fdfe911ae4994f5f295b2f5e15aae721bb7e1268fb9bd1d42c8e24be78c76da6	["*"]	2026-03-18 09:13:47	\N	2026-03-18 09:13:22	2026-03-18 09:13:47
27	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	commerce-test	aa68c6ddba2c145f1df624ddb60202c85e3496c66df1d0503080b25288a26e35	["*"]	2026-03-18 10:01:09	\N	2026-03-18 10:01:07	2026-03-18 10:01:09
25	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	inv-test2	01df77ef934d721e317548066bb61965eacc8e1a20ca28e6b5d4babe0730ffd7	["*"]	2026-03-18 09:38:37	\N	2026-03-18 09:38:34	2026-03-18 09:38:37
22	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	inv-test	5f6f9f143f81969978543a9d272fd0479c99db3f56924e4ee8537d84bc367329	["*"]	2026-03-18 09:36:09	\N	2026-03-18 09:36:06	2026-03-18 09:36:09
30	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	comms-test	7c937ee1bc55fdbae8ee6ab89dbf04e4f291ecd8307f0d9a6a46ce119984e759	["*"]	2026-03-18 12:25:49	\N	2026-03-18 12:25:46	2026-03-18 12:25:49
29	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	comms-test	736c97e6a5910a069be4d14b33f755c5f0f88e9da22385e8cf02de2135a76320	["*"]	2026-03-18 12:19:36	\N	2026-03-18 12:19:34	2026-03-18 12:19:36
33	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	pharma-test	99380b941ae85c2651a0667df3dfa55b6a5189996de42b5727033836e2f5fc60	["*"]	2026-03-19 13:55:50	\N	2026-03-19 13:55:46	2026-03-19 13:55:50
34	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	lg-test	4c52c9307d20188ae502c7cb785b83cfcb206aae2ec0945745e826b71bdd2134	["*"]	\N	\N	2026-04-10 10:49:49	2026-04-10 10:49:49
32	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	notif-test	3fdc2cf8b3cbc84d57f1938b690f6777c3f5aff88f5cbaed547b9cdfb848e1ff	["*"]	2026-03-18 19:17:40	\N	2026-03-18 19:17:37	2026-03-18 19:17:40
31	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	comms-test	cd1ac3b2c99d1f26316c104d857e7992d9c94c6c59a5dbca88d45ca194397d9d	["*"]	2026-03-18 18:42:51	\N	2026-03-18 18:42:48	2026-03-18 18:42:51
35	Modules\\Platform\\Models\\User	01KKXYVPYCP7W43V8MED2CGVY7	lg-test	fc26cc55e8b010542e90621e76dc692799077451d6c66c077def7bb3fadadd60	["*"]	2026-04-10 10:55:23	\N	2026-04-10 10:55:13	2026-04-10 10:55:23
37	Modules\\Platform\\Models\\User	01KP5XBQBRDWG9M8GP2TN12X2T	admin	5ff797dc39788f8a8b507ac4ca4f7b5743299b6e304b21e45de34b505164377a	["*"]	\N	\N	2026-04-14 11:52:35	2026-04-14 11:52:35
36	Modules\\Platform\\Models\\User	01KP5W249DN4F9N24ZX2AMXGX6	api	0913f4cb3ab8f02b98af9f1c4d027eba78bd0422c1c8ee4c3d1cf3578fa6f0ff	["*"]	2026-04-14 12:01:44	\N	2026-04-14 11:29:39	2026-04-14 12:01:44
38	Modules\\Platform\\Models\\User	01KP5XBQBRDWG9M8GP2TN12X2T	api	1d0b0e6901c1faa702e315252a6a99b6d00dad206c6f599d074ca5ab1a3e3683	["*"]	2026-04-14 14:49:49	\N	2026-04-14 11:53:07	2026-04-14 14:49:49
\.


--
-- Data for Name: platform_default_role_permissions; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.platform_default_role_permissions (default_role_id, org_permission_def_id) FROM stdin;
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76V96F6K3BZXM2JFJ03R5
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76V9ZSZVMVEQBS7QEJQ11
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VA0NJ1C908NZ4V68T9K
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VA0NJ1C908NZ4V68T9M
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VA7PQF9FV85H6Y5QX20
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VA8AP87YYREK9T932XZ
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VA8AP87YYREK9T932Y0
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VA9PR6FM0R6BSXZ2MF9
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VA9PR6FM0R6BSXZ2MFA
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAAV4JFNKXTD55Z0KTJ
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAB6K7R5Z1P31XB6S20
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VACZF99TMTPQJ6CRYNX
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAD1DAJFWQXA8F0H3FC
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAD1DAJFWQXA8F0H3FD
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAE7RZH8CA13Y406CCQ
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAE7RZH8CA13Y406CCR
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAFQS2PVDKA1ZG8QKGE
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAFQS2PVDKA1ZG8QKGF
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAGH1HHJB9TY6CW72DA
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAGH1HHJB9TY6CW72DB
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAGH1HHJB9TY6CW72DC
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAHG04JH5NBZMP0BVP8
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAHG04JH5NBZMP0BVP9
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAHG04JH5NBZMP0BVPA
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAJRT81KR0EVWR02KCS
01KKY76VBB1ST5NXQBA4FAS3Q7	01KKY76VAJRT81KR0EVWR02KCT
01KKY76VD4YG13GG9SAWM35WRM	01KKY76V96F6K3BZXM2JFJ03R5
01KKY76VD4YG13GG9SAWM35WRM	01KKY76V9ZSZVMVEQBS7QEJQ11
01KKY76VD4YG13GG9SAWM35WRM	01KKY76VA8AP87YYREK9T932Y0
01KKY76VD4YG13GG9SAWM35WRM	01KKY76VAD1DAJFWQXA8F0H3FC
01KKY76VD4YG13GG9SAWM35WRM	01KKY76VAD1DAJFWQXA8F0H3FD
01KKY76VD4YG13GG9SAWM35WRM	01KKY76VAE7RZH8CA13Y406CCQ
01KKY76VD4YG13GG9SAWM35WRM	01KKY76VAFQS2PVDKA1ZG8QKGE
01KKY76VD4YG13GG9SAWM35WRM	01KKY76VAFQS2PVDKA1ZG8QKGF
01KKY76VD4YG13GG9SAWM35WRM	01KKY76VAGH1HHJB9TY6CW72DA
01KKY76VD4YG13GG9SAWM35WRM	01KKY76VAGH1HHJB9TY6CW72DC
01KKY76VD4YG13GG9SAWM35WRM	01KKY76VAHG04JH5NBZMP0BVP9
01KKY76VD4YG13GG9SAWM35WRM	01KKY76VAJRT81KR0EVWR02KCS
01KKY76VD4YG13GG9SAWM35WRM	01KKY76VAJRT81KR0EVWR02KCT
01KKY76VDEM2F3KDXWD6KD8957	01KKY76V96F6K3BZXM2JFJ03R5
01KKY76VDEM2F3KDXWD6KD8957	01KKY76VAD1DAJFWQXA8F0H3FC
01KKY76VDEM2F3KDXWD6KD8957	01KKY76VAD1DAJFWQXA8F0H3FD
01KKY76VDEM2F3KDXWD6KD8957	01KKY76VAFQS2PVDKA1ZG8QKGE
01KKY76VDEM2F3KDXWD6KD8957	01KKY76VAGH1HHJB9TY6CW72DC
01KKY76VDEM2F3KDXWD6KD8957	01KKY76VAJRT81KR0EVWR02KCS
01KKY76VDEM2F3KDXWD6KD8957	01KKY76VAJRT81KR0EVWR02KCT
01KKY76VDKB01XJJQ87JRH3G74	01KKY76V96F6K3BZXM2JFJ03R5
01KKY76VDKB01XJJQ87JRH3G74	01KKY76VAD1DAJFWQXA8F0H3FC
01KKY76VDKB01XJJQ87JRH3G74	01KKY76VAFQS2PVDKA1ZG8QKGE
01KKY76VDKB01XJJQ87JRH3G74	01KKY76VAGH1HHJB9TY6CW72DC
01KKY76VDKB01XJJQ87JRH3G74	01KKY76VAHG04JH5NBZMP0BVP9
01KKY76VDKB01XJJQ87JRH3G74	01KKY76VAJRT81KR0EVWR02KCS
\.


--
-- Data for Name: platform_default_roles; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.platform_default_roles (id, name, description, is_active, created_at, updated_at) FROM stdin;
01KKY76VBB1ST5NXQBA4FAS3Q7	org_admin	Full access within the org node.	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VD4YG13GG9SAWM35WRM	manager	Manages day-to-day operations.	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VDEM2F3KDXWD6KD8957	staff	Standard operational access.	t	2026-03-17 15:39:09	2026-03-17 15:39:09
01KKY76VDKB01XJJQ87JRH3G74	viewer	Read-only access.	t	2026-03-17 15:39:09	2026-03-17 15:39:09
\.


--
-- Data for Name: platform_feature_flags; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.platform_feature_flags (id, key, value, description, module, updated_by, created_at, updated_at) FROM stdin;
01KKXYTS17JXAW0P5PVDB458ZF	platform.registration_open	t	Allow new user registrations	identity	\N	2026-03-17 13:12:44	2026-03-17 13:12:44
01KKXYTS1AC8WHTVAQMV6X1MYT	platform.org_self_signup	t	Allow orgs to self-register	organizations	\N	2026-03-17 13:12:44	2026-03-17 13:12:44
01KKXYTS1BV23DZNXY74SJV49N	marketplace.enabled	f	Enable marketplace module	marketplace	\N	2026-03-17 13:12:44	2026-03-17 13:12:44
01KKXYTS1EHAG3M16PHXY1VWA7	social.enabled	f	Enable social/community module	social	\N	2026-03-17 13:12:44	2026-03-17 13:12:44
01KKY0P8F25SGY0T6PNE4Y8X0Z	orgs.approval_required_permission	t	Permission required to approve organizations. Default: orgs.approve	organizations	\N	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKXYTS1DZ17B8K1V47V9ERBZ	communications.enabled	t	Enable communications module	communications	01KKXYVPYCP7W43V8MED2CGVY7	2026-03-17 13:12:44	2026-03-17 13:56:20
\.


--
-- Data for Name: platform_permissions; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.platform_permissions (id, name, group_name, description, is_active, created_at, updated_at) FROM stdin;
01KKY0P8CF77RYMFE8SHX9V9DT	staff.view	staff	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8CKF1ZZ78J7TKX4XJPV	staff.assign	staff	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8CMBX15FPXZ499K9KQ7	staff.revoke	staff	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8CN9HD9R1T01NWG194D	orgs.view	organizations	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8CPZ55X6C52JTMS8K9E	orgs.approve	organizations	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8CQD9T38ACTBK56H372	orgs.reject	organizations	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8CRXVMXX3N5BVP83NH0	orgs.suspend	organizations	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8CSQNQPKBXJBY43CQV1	orgs.reactivate	organizations	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8CSQNQPKBXJBY43CQV2	users.view	users	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8CT443099QD9VCTX2BK	users.suspend	users	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8CWJTD59GECHM6JGVDB	users.ban	users	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8CYJENCC8PKXBZBSSJ7	users.tier.assign	users	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8CZ6PKPBVZS0CHN2B70	flags.view	feature_flags	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8D0XVCCRP6N5ZH22W9R	flags.toggle	feature_flags	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8D1XYVPYBGJDEAK5PRT	tiers.view	tiers	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8D2FE8KSAJRWBZD8K0E	tiers.manage	tiers	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
01KKY0P8D3VR3SD0NSJEAXCF6J	audit.view	audit	\N	t	2026-03-17 13:45:13	2026-03-17 13:45:13
\.


--
-- Data for Name: platform_role_permissions; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.platform_role_permissions (platform_role_id, platform_permission_id) FROM stdin;
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8CF77RYMFE8SHX9V9DT
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8CKF1ZZ78J7TKX4XJPV
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8CMBX15FPXZ499K9KQ7
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8CN9HD9R1T01NWG194D
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8CPZ55X6C52JTMS8K9E
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8CQD9T38ACTBK56H372
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8CRXVMXX3N5BVP83NH0
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8CSQNQPKBXJBY43CQV1
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8CSQNQPKBXJBY43CQV2
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8CT443099QD9VCTX2BK
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8CWJTD59GECHM6JGVDB
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8CYJENCC8PKXBZBSSJ7
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8CZ6PKPBVZS0CHN2B70
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8D0XVCCRP6N5ZH22W9R
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8D1XYVPYBGJDEAK5PRT
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8D2FE8KSAJRWBZD8K0E
01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKY0P8D3VR3SD0NSJEAXCF6J
01KKXYTS0WY9CE8AXRDS01PS2S	01KKY0P8CN9HD9R1T01NWG194D
01KKXYTS0WY9CE8AXRDS01PS2S	01KKY0P8CSQNQPKBXJBY43CQV2
01KKXYTS0WY9CE8AXRDS01PS2S	01KKY0P8D3VR3SD0NSJEAXCF6J
01KKXYTS0WY9CE8AXRDS01PS2T	01KKY0P8CN9HD9R1T01NWG194D
01KKXYTS0WY9CE8AXRDS01PS2T	01KKY0P8CSQNQPKBXJBY43CQV2
01KKXYTS0WY9CE8AXRDS01PS2T	01KKY0P8CYJENCC8PKXBZBSSJ7
01KKXYTS0WY9CE8AXRDS01PS2T	01KKY0P8D1XYVPYBGJDEAK5PRT
01KKXYTS0WY9CE8AXRDS01PS2T	01KKY0P8D2FE8KSAJRWBZD8K0E
01KKXYTS0WY9CE8AXRDS01PS2T	01KKY0P8D3VR3SD0NSJEAXCF6J
01KKXYTS0XRA0QXX2RACJ862B9	01KKY0P8CN9HD9R1T01NWG194D
01KKXYTS0XRA0QXX2RACJ862B9	01KKY0P8CZ6PKPBVZS0CHN2B70
01KKXYTS0XRA0QXX2RACJ862B9	01KKY0P8D0XVCCRP6N5ZH22W9R
\.


--
-- Data for Name: platform_roles; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.platform_roles (id, name, description, is_system, created_at, updated_at) FROM stdin;
01KKXYTS0SNMZT4V5ZY0TNDZ60	super_admin	Full platform access. All permissions.	t	2026-03-17 13:12:44	2026-03-17 13:12:44
01KKXYTS0WY9CE8AXRDS01PS2S	support_agent	Read access to orgs and users for support.	t	2026-03-17 13:12:44	2026-03-17 13:12:44
01KKXYTS0WY9CE8AXRDS01PS2T	billing_admin	Manage subscriptions, invoices, and payments.	t	2026-03-17 13:12:44	2026-03-17 13:12:44
01KKXYTS0XRA0QXX2RACJ862B9	content_admin	Manage platform content and announcements.	t	2026-03-17 13:12:44	2026-03-17 13:12:44
\.


--
-- Data for Name: platform_tiers; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.platform_tiers (id, name, description, is_default, is_active, sort_order, created_at, updated_at) FROM stdin;
01KKXYTS1092ZB85RR9R5F65VM	free	Free tier. Basic platform access.	t	t	0	2026-03-17 13:12:44	2026-03-17 13:12:44
01KKXYTS136ERCT8GS3YAP5FK0	premium	Premium tier. Enhanced feature access.	f	t	1	2026-03-17 13:12:44	2026-03-17 13:12:44
01KKXYTS136ERCT8GS3YAP5FK1	enterprise	Enterprise tier. Full platform access.	f	t	2	2026-03-17 13:12:44	2026-03-17 13:12:44
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
a0VhSlTQrywx3e9QvSDHwj12zJzIzp7k64o8Hed4	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoiMHFUT1dTazA1ZWpaeXpqaFJpbUpubHJVOGZUbWp0ZDJIRjFiNjJyWCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1776170532
\.


--
-- Data for Name: user_platform_roles; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.user_platform_roles (user_id, platform_role_id, granted_by, granted_at) FROM stdin;
01KKXYVPYCP7W43V8MED2CGVY7	01KKXYTS0SNMZT4V5ZY0TNDZ60	01KKXYVPYCP7W43V8MED2CGVY7	2026-03-17 13:49:58
01KKY1ARTMV5DNN8C9W4H33FJM	01KKXYTS0WY9CE8AXRDS01PS2S	01KKXYVPYCP7W43V8MED2CGVY7	2026-03-17 13:56:32
01KP5XBQBRDWG9M8GP2TN12X2T	01KKXYTS0SNMZT4V5ZY0TNDZ60	\N	2026-04-14 14:52:23
\.


--
-- Data for Name: user_social_logins; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.user_social_logins (id, user_id, provider, provider_id, access_token, refresh_token, expires_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: user_tier_assignments; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.user_tier_assignments (id, user_id, tier_id, assigned_by, starts_at, expires_at, status, created_at, updated_at) FROM stdin;
01KKXYVPYQJ49K8CJ5N6G7VZHM	01KKXYVPYCP7W43V8MED2CGVY7	01KKXYTS1092ZB85RR9R5F65VM	\N	2026-03-17 16:13:15	\N	active	2026-03-17 13:13:15	2026-03-17 13:13:15
01KKY1ARTYJANSEFZ6KYT613RY	01KKY1ARTMV5DNN8C9W4H33FJM	01KKXYTS1092ZB85RR9R5F65VM	\N	2026-03-17 16:56:26	\N	active	2026-03-17 13:56:26	2026-03-17 13:56:26
01KKZZCZ4JABCWEQ8BMXXHE7Z2	01KKZZCZ2W9CMX2J40NK658G9G	01KKXYTS1092ZB85RR9R5F65VM	\N	2026-03-18 11:01:09	\N	active	2026-03-18 08:01:09	2026-03-18 08:01:09
01KP5W24CZXG0SG723EEXMP51Q	01KP5W249DN4F9N24ZX2AMXGX6	01KKXYTS1092ZB85RR9R5F65VM	\N	2026-04-14 14:29:39	\N	active	2026-04-14 11:29:39	2026-04-14 11:29:39
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: platform; Owner: postgres
--

COPY platform.users (id, email, email_verified_at, password, remember_token, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, actor_id, status, last_login_at, last_login_ip, created_at, updated_at, deleted_at, username) FROM stdin;
01KKXYVPYCP7W43V8MED2CGVY7	test2@nexora.dev	\N	$2y$12$LQvRBb/3Z1yfpssUbKvht.czNjqadVohlYyIyjRxNQYnAXrU.FWSG	\N	\N	\N	\N	01KKXYVPQ90WV3RWBQHJQ79BQM	active	\N	\N	2026-03-17 13:13:15	2026-03-17 13:13:15	\N	testuser2
01KKY1ARTMV5DNN8C9W4H33FJM	support@nexora.dev	\N	$2y$12$OFuShEV6LRXi1I4/6hYTx.JBEEZn88Hdpgtpr.HbeY/46dK2YM8NG	\N	\N	\N	\N	01KKY1ARKP3E1BY0K2GCYQEA34	active	\N	\N	2026-03-17 13:56:26	2026-03-17 13:56:26	\N	supportagent1
01KKZZCZ2W9CMX2J40NK658G9G	eventtest1@nexora.dev	\N	$2y$12$j8fyjz8lUmQs.lw3.rVn7.5DC20.BoyagR5miJpG40xzzAdayodwi	\N	\N	\N	\N	01KKZZCYRF08A3ANG20KQY6XCP	active	\N	\N	2026-03-18 08:01:09	2026-03-18 08:01:09	\N	eventtest1
01KP5W249DN4F9N24ZX2AMXGX6	elia@elia.elia	\N	$2y$12$RPi8tNxcl5ZGs71uI6iAKOjeQBNfUM8Id0iSNrfE.oWdagGOMbwc6	\N	\N	\N	\N	01KP5W23VFZNEZ1P8F7YXPZY60	active	\N	\N	2026-04-14 11:29:39	2026-04-14 11:29:39	\N	Elia
01KP5XBQBRDWG9M8GP2TN12X2T	admin@yourdomain.com	\N	$2y$12$Gdtl0dT15Pf1zDJqrij/seldARgERMiTecmR1tQhdd3.stq6bhsl.	\N	\N	\N	\N	01KP5XBQMA2P4V7VDDJ4EFTVFJ	active	\N	\N	2026-04-14 11:52:22	2026-04-14 11:52:22	\N	superadmin
\.


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: platform; Owner: postgres
--

SELECT pg_catalog.setval('platform.failed_jobs_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: platform; Owner: postgres
--

SELECT pg_catalog.setval('platform.jobs_id_seq', 1, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: platform; Owner: postgres
--

SELECT pg_catalog.setval('platform.migrations_id_seq', 123, true);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: platform; Owner: postgres
--

SELECT pg_catalog.setval('platform.personal_access_tokens_id_seq', 38, true);


--
-- Name: activity_logs activity_logs_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.activity_logs
    ADD CONSTRAINT activity_logs_pkey PRIMARY KEY (id);


--
-- Name: actor_relationships actor_relationships_actor_id_related_actor_id_relationship_type; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.actor_relationships
    ADD CONSTRAINT actor_relationships_actor_id_related_actor_id_relationship_type UNIQUE (actor_id, related_actor_id, relationship_type, source_module);


--
-- Name: actor_relationships actor_relationships_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.actor_relationships
    ADD CONSTRAINT actor_relationships_pkey PRIMARY KEY (id);


--
-- Name: actor_type_assignments actor_type_assignments_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.actor_type_assignments
    ADD CONSTRAINT actor_type_assignments_pkey PRIMARY KEY (actor_id, actor_type_id);


--
-- Name: actor_types actor_types_name_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.actor_types
    ADD CONSTRAINT actor_types_name_unique UNIQUE (name);


--
-- Name: actor_types actor_types_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.actor_types
    ADD CONSTRAINT actor_types_pkey PRIMARY KEY (id);


--
-- Name: actors actors_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.actors
    ADD CONSTRAINT actors_pkey PRIMARY KEY (id);


--
-- Name: audit_log_configs audit_log_configs_module_action_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.audit_log_configs
    ADD CONSTRAINT audit_log_configs_module_action_unique UNIQUE (module, action);


--
-- Name: audit_log_configs audit_log_configs_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.audit_log_configs
    ADD CONSTRAINT audit_log_configs_pkey PRIMARY KEY (id);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: event_dispatch_log event_dispatch_log_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.event_dispatch_log
    ADD CONSTRAINT event_dispatch_log_pkey PRIMARY KEY (id);


--
-- Name: event_registry event_registry_name_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.event_registry
    ADD CONSTRAINT event_registry_name_unique UNIQUE (name);


--
-- Name: event_registry event_registry_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.event_registry
    ADD CONSTRAINT event_registry_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: org_delegation_permissions org_delegation_permissions_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_delegation_permissions
    ADD CONSTRAINT org_delegation_permissions_pkey PRIMARY KEY (delegation_id, org_permission_def_id);


--
-- Name: org_invitations org_invitations_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_invitations
    ADD CONSTRAINT org_invitations_pkey PRIMARY KEY (id);


--
-- Name: org_invitations org_invitations_token_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_invitations
    ADD CONSTRAINT org_invitations_token_unique UNIQUE (token);


--
-- Name: org_memberships org_memberships_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_memberships
    ADD CONSTRAINT org_memberships_pkey PRIMARY KEY (id);


--
-- Name: org_memberships org_memberships_user_id_org_id_org_role_id_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_memberships
    ADD CONSTRAINT org_memberships_user_id_org_id_org_role_id_unique UNIQUE (user_id, org_id, org_role_id);


--
-- Name: org_permission_definitions org_permission_definitions_name_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_permission_definitions
    ADD CONSTRAINT org_permission_definitions_name_unique UNIQUE (name);


--
-- Name: org_permission_definitions org_permission_definitions_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_permission_definitions
    ADD CONSTRAINT org_permission_definitions_pkey PRIMARY KEY (id);


--
-- Name: org_permission_requests org_permission_requests_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_permission_requests
    ADD CONSTRAINT org_permission_requests_pkey PRIMARY KEY (id);


--
-- Name: org_role_delegations org_role_delegations_parent_org_id_child_org_id_org_role_id_uni; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_role_delegations
    ADD CONSTRAINT org_role_delegations_parent_org_id_child_org_id_org_role_id_uni UNIQUE (parent_org_id, child_org_id, org_role_id);


--
-- Name: org_role_delegations org_role_delegations_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_role_delegations
    ADD CONSTRAINT org_role_delegations_pkey PRIMARY KEY (id);


--
-- Name: org_role_permissions org_role_permissions_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_role_permissions
    ADD CONSTRAINT org_role_permissions_pkey PRIMARY KEY (org_role_id, org_permission_def_id);


--
-- Name: org_roles org_roles_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_roles
    ADD CONSTRAINT org_roles_pkey PRIMARY KEY (id);


--
-- Name: org_roles org_roles_root_org_id_name_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_roles
    ADD CONSTRAINT org_roles_root_org_id_name_unique UNIQUE (root_org_id, name);


--
-- Name: org_scope_grant_branches org_scope_grant_branches_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_scope_grant_branches
    ADD CONSTRAINT org_scope_grant_branches_pkey PRIMARY KEY (scope_grant_id, org_id);


--
-- Name: org_scope_grants org_scope_grants_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_scope_grants
    ADD CONSTRAINT org_scope_grants_pkey PRIMARY KEY (id);


--
-- Name: org_scope_requests org_scope_requests_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_scope_requests
    ADD CONSTRAINT org_scope_requests_pkey PRIMARY KEY (id);


--
-- Name: organizations organizations_actor_id_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.organizations
    ADD CONSTRAINT organizations_actor_id_unique UNIQUE (actor_id);


--
-- Name: organizations organizations_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.organizations
    ADD CONSTRAINT organizations_pkey PRIMARY KEY (id);


--
-- Name: organizations organizations_slug_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.organizations
    ADD CONSTRAINT organizations_slug_unique UNIQUE (slug);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: platform_default_role_permissions platform_default_role_permissions_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_default_role_permissions
    ADD CONSTRAINT platform_default_role_permissions_pkey PRIMARY KEY (default_role_id, org_permission_def_id);


--
-- Name: platform_default_roles platform_default_roles_name_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_default_roles
    ADD CONSTRAINT platform_default_roles_name_unique UNIQUE (name);


--
-- Name: platform_default_roles platform_default_roles_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_default_roles
    ADD CONSTRAINT platform_default_roles_pkey PRIMARY KEY (id);


--
-- Name: platform_feature_flags platform_feature_flags_key_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_feature_flags
    ADD CONSTRAINT platform_feature_flags_key_unique UNIQUE (key);


--
-- Name: platform_feature_flags platform_feature_flags_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_feature_flags
    ADD CONSTRAINT platform_feature_flags_pkey PRIMARY KEY (id);


--
-- Name: platform_permissions platform_permissions_name_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_permissions
    ADD CONSTRAINT platform_permissions_name_unique UNIQUE (name);


--
-- Name: platform_permissions platform_permissions_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_permissions
    ADD CONSTRAINT platform_permissions_pkey PRIMARY KEY (id);


--
-- Name: platform_role_permissions platform_role_permissions_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_role_permissions
    ADD CONSTRAINT platform_role_permissions_pkey PRIMARY KEY (platform_role_id, platform_permission_id);


--
-- Name: platform_roles platform_roles_name_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_roles
    ADD CONSTRAINT platform_roles_name_unique UNIQUE (name);


--
-- Name: platform_roles platform_roles_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_roles
    ADD CONSTRAINT platform_roles_pkey PRIMARY KEY (id);


--
-- Name: platform_tiers platform_tiers_name_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_tiers
    ADD CONSTRAINT platform_tiers_name_unique UNIQUE (name);


--
-- Name: platform_tiers platform_tiers_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_tiers
    ADD CONSTRAINT platform_tiers_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: user_platform_roles user_platform_roles_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.user_platform_roles
    ADD CONSTRAINT user_platform_roles_pkey PRIMARY KEY (user_id, platform_role_id);


--
-- Name: user_social_logins user_social_logins_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.user_social_logins
    ADD CONSTRAINT user_social_logins_pkey PRIMARY KEY (id);


--
-- Name: user_social_logins user_social_logins_provider_provider_id_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.user_social_logins
    ADD CONSTRAINT user_social_logins_provider_provider_id_unique UNIQUE (provider, provider_id);


--
-- Name: user_tier_assignments user_tier_assignments_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.user_tier_assignments
    ADD CONSTRAINT user_tier_assignments_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_unique; Type: CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.users
    ADD CONSTRAINT users_username_unique UNIQUE (username);


--
-- Name: activity_logs_action_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX activity_logs_action_index ON platform.activity_logs USING btree (action);


--
-- Name: activity_logs_actor_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX activity_logs_actor_id_index ON platform.activity_logs USING btree (actor_id);


--
-- Name: activity_logs_entity_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX activity_logs_entity_id_index ON platform.activity_logs USING btree (entity_id);


--
-- Name: activity_logs_entity_type_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX activity_logs_entity_type_index ON platform.activity_logs USING btree (entity_type);


--
-- Name: activity_logs_occurred_at_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX activity_logs_occurred_at_index ON platform.activity_logs USING btree (occurred_at);


--
-- Name: activity_logs_org_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX activity_logs_org_id_index ON platform.activity_logs USING btree (org_id);


--
-- Name: actor_relationships_actor_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX actor_relationships_actor_id_index ON platform.actor_relationships USING btree (actor_id);


--
-- Name: actor_relationships_related_actor_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX actor_relationships_related_actor_id_index ON platform.actor_relationships USING btree (related_actor_id);


--
-- Name: actor_relationships_status_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX actor_relationships_status_index ON platform.actor_relationships USING btree (status);


--
-- Name: audit_logs_action_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX audit_logs_action_index ON platform.audit_logs USING btree (action);


--
-- Name: audit_logs_actor_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX audit_logs_actor_id_index ON platform.audit_logs USING btree (actor_id);


--
-- Name: audit_logs_created_at_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX audit_logs_created_at_index ON platform.audit_logs USING btree (created_at);


--
-- Name: audit_logs_module_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX audit_logs_module_index ON platform.audit_logs USING btree (module);


--
-- Name: audit_logs_subject_type_subject_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX audit_logs_subject_type_subject_id_index ON platform.audit_logs USING btree (subject_type, subject_id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX cache_expiration_index ON platform.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX cache_locks_expiration_index ON platform.cache_locks USING btree (expiration);


--
-- Name: event_dispatch_log_actor_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX event_dispatch_log_actor_id_index ON platform.event_dispatch_log USING btree (actor_id);


--
-- Name: event_dispatch_log_event_name_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX event_dispatch_log_event_name_index ON platform.event_dispatch_log USING btree (event_name);


--
-- Name: event_dispatch_log_fired_at_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX event_dispatch_log_fired_at_index ON platform.event_dispatch_log USING btree (fired_at);


--
-- Name: event_dispatch_log_status_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX event_dispatch_log_status_index ON platform.event_dispatch_log USING btree (status);


--
-- Name: event_registry_module_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX event_registry_module_index ON platform.event_registry USING btree (module);


--
-- Name: idx_organizations_path; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX idx_organizations_path ON platform.organizations USING gist (path);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX jobs_queue_index ON platform.jobs USING btree (queue);


--
-- Name: org_invitations_email_status_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX org_invitations_email_status_index ON platform.org_invitations USING btree (email, status);


--
-- Name: org_invitations_org_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX org_invitations_org_id_index ON platform.org_invitations USING btree (org_id);


--
-- Name: org_memberships_org_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX org_memberships_org_id_index ON platform.org_memberships USING btree (org_id);


--
-- Name: org_memberships_status_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX org_memberships_status_index ON platform.org_memberships USING btree (status);


--
-- Name: org_memberships_user_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX org_memberships_user_id_index ON platform.org_memberships USING btree (user_id);


--
-- Name: org_permission_definitions_group_name_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX org_permission_definitions_group_name_index ON platform.org_permission_definitions USING btree (group_name);


--
-- Name: org_permission_requests_requesting_org_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX org_permission_requests_requesting_org_id_index ON platform.org_permission_requests USING btree (requesting_org_id);


--
-- Name: org_permission_requests_status_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX org_permission_requests_status_index ON platform.org_permission_requests USING btree (status);


--
-- Name: org_scope_requests_status_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX org_scope_requests_status_index ON platform.org_scope_requests USING btree (status);


--
-- Name: organizations_parent_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX organizations_parent_id_index ON platform.organizations USING btree (parent_id);


--
-- Name: organizations_root_org_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX organizations_root_org_id_index ON platform.organizations USING btree (root_org_id);


--
-- Name: organizations_status_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX organizations_status_index ON platform.organizations USING btree (status);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX personal_access_tokens_expires_at_index ON platform.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON platform.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: platform_permissions_group_name_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX platform_permissions_group_name_index ON platform.platform_permissions USING btree (group_name);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON platform.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON platform.sessions USING btree (user_id);


--
-- Name: user_tier_assignments_status_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX user_tier_assignments_status_index ON platform.user_tier_assignments USING btree (status);


--
-- Name: user_tier_assignments_user_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX user_tier_assignments_user_id_index ON platform.user_tier_assignments USING btree (user_id);


--
-- Name: users_actor_id_index; Type: INDEX; Schema: platform; Owner: postgres
--

CREATE INDEX users_actor_id_index ON platform.users USING btree (actor_id);


--
-- Name: actor_relationships actor_relationships_actor_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.actor_relationships
    ADD CONSTRAINT actor_relationships_actor_id_foreign FOREIGN KEY (actor_id) REFERENCES platform.actors(id);


--
-- Name: actor_relationships actor_relationships_related_actor_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.actor_relationships
    ADD CONSTRAINT actor_relationships_related_actor_id_foreign FOREIGN KEY (related_actor_id) REFERENCES platform.actors(id);


--
-- Name: actor_type_assignments actor_type_assignments_actor_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.actor_type_assignments
    ADD CONSTRAINT actor_type_assignments_actor_id_foreign FOREIGN KEY (actor_id) REFERENCES platform.actors(id) ON DELETE CASCADE;


--
-- Name: actor_type_assignments actor_type_assignments_actor_type_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.actor_type_assignments
    ADD CONSTRAINT actor_type_assignments_actor_type_id_foreign FOREIGN KEY (actor_type_id) REFERENCES platform.actor_types(id);


--
-- Name: actor_type_assignments actor_type_assignments_assigned_by_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.actor_type_assignments
    ADD CONSTRAINT actor_type_assignments_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES platform.actors(id) ON DELETE SET NULL;


--
-- Name: audit_logs audit_logs_actor_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.audit_logs
    ADD CONSTRAINT audit_logs_actor_id_foreign FOREIGN KEY (actor_id) REFERENCES platform.actors(id) ON DELETE SET NULL;


--
-- Name: event_dispatch_log event_dispatch_log_actor_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.event_dispatch_log
    ADD CONSTRAINT event_dispatch_log_actor_id_foreign FOREIGN KEY (actor_id) REFERENCES platform.actors(id) ON DELETE SET NULL;


--
-- Name: org_delegation_permissions org_delegation_permissions_delegation_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_delegation_permissions
    ADD CONSTRAINT org_delegation_permissions_delegation_id_foreign FOREIGN KEY (delegation_id) REFERENCES platform.org_role_delegations(id) ON DELETE CASCADE;


--
-- Name: org_delegation_permissions org_delegation_permissions_org_permission_def_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_delegation_permissions
    ADD CONSTRAINT org_delegation_permissions_org_permission_def_id_foreign FOREIGN KEY (org_permission_def_id) REFERENCES platform.org_permission_definitions(id);


--
-- Name: org_invitations org_invitations_invited_by_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_invitations
    ADD CONSTRAINT org_invitations_invited_by_foreign FOREIGN KEY (invited_by) REFERENCES platform.users(id);


--
-- Name: org_invitations org_invitations_org_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_invitations
    ADD CONSTRAINT org_invitations_org_id_foreign FOREIGN KEY (org_id) REFERENCES platform.organizations(id) ON DELETE CASCADE;


--
-- Name: org_invitations org_invitations_org_role_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_invitations
    ADD CONSTRAINT org_invitations_org_role_id_foreign FOREIGN KEY (org_role_id) REFERENCES platform.org_roles(id) ON DELETE CASCADE;


--
-- Name: org_memberships org_memberships_invited_by_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_memberships
    ADD CONSTRAINT org_memberships_invited_by_foreign FOREIGN KEY (invited_by) REFERENCES platform.users(id) ON DELETE SET NULL;


--
-- Name: org_memberships org_memberships_org_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_memberships
    ADD CONSTRAINT org_memberships_org_id_foreign FOREIGN KEY (org_id) REFERENCES platform.organizations(id);


--
-- Name: org_memberships org_memberships_org_role_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_memberships
    ADD CONSTRAINT org_memberships_org_role_id_foreign FOREIGN KEY (org_role_id) REFERENCES platform.org_roles(id);


--
-- Name: org_memberships org_memberships_user_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_memberships
    ADD CONSTRAINT org_memberships_user_id_foreign FOREIGN KEY (user_id) REFERENCES platform.users(id);


--
-- Name: org_permission_requests org_permission_requests_org_permission_def_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_permission_requests
    ADD CONSTRAINT org_permission_requests_org_permission_def_id_foreign FOREIGN KEY (org_permission_def_id) REFERENCES platform.org_permission_definitions(id);


--
-- Name: org_permission_requests org_permission_requests_org_role_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_permission_requests
    ADD CONSTRAINT org_permission_requests_org_role_id_foreign FOREIGN KEY (org_role_id) REFERENCES platform.org_roles(id);


--
-- Name: org_permission_requests org_permission_requests_requesting_org_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_permission_requests
    ADD CONSTRAINT org_permission_requests_requesting_org_id_foreign FOREIGN KEY (requesting_org_id) REFERENCES platform.organizations(id);


--
-- Name: org_permission_requests org_permission_requests_reviewed_by_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_permission_requests
    ADD CONSTRAINT org_permission_requests_reviewed_by_foreign FOREIGN KEY (reviewed_by) REFERENCES platform.users(id) ON DELETE SET NULL;


--
-- Name: org_permission_requests org_permission_requests_target_org_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_permission_requests
    ADD CONSTRAINT org_permission_requests_target_org_id_foreign FOREIGN KEY (target_org_id) REFERENCES platform.organizations(id);


--
-- Name: org_role_delegations org_role_delegations_child_org_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_role_delegations
    ADD CONSTRAINT org_role_delegations_child_org_id_foreign FOREIGN KEY (child_org_id) REFERENCES platform.organizations(id);


--
-- Name: org_role_delegations org_role_delegations_granted_by_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_role_delegations
    ADD CONSTRAINT org_role_delegations_granted_by_foreign FOREIGN KEY (granted_by) REFERENCES platform.users(id);


--
-- Name: org_role_delegations org_role_delegations_org_role_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_role_delegations
    ADD CONSTRAINT org_role_delegations_org_role_id_foreign FOREIGN KEY (org_role_id) REFERENCES platform.org_roles(id);


--
-- Name: org_role_delegations org_role_delegations_parent_org_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_role_delegations
    ADD CONSTRAINT org_role_delegations_parent_org_id_foreign FOREIGN KEY (parent_org_id) REFERENCES platform.organizations(id);


--
-- Name: org_role_permissions org_role_permissions_org_permission_def_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_role_permissions
    ADD CONSTRAINT org_role_permissions_org_permission_def_id_foreign FOREIGN KEY (org_permission_def_id) REFERENCES platform.org_permission_definitions(id) ON DELETE CASCADE;


--
-- Name: org_role_permissions org_role_permissions_org_role_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_role_permissions
    ADD CONSTRAINT org_role_permissions_org_role_id_foreign FOREIGN KEY (org_role_id) REFERENCES platform.org_roles(id) ON DELETE CASCADE;


--
-- Name: org_roles org_roles_default_role_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_roles
    ADD CONSTRAINT org_roles_default_role_id_foreign FOREIGN KEY (default_role_id) REFERENCES platform.platform_default_roles(id) ON DELETE SET NULL;


--
-- Name: org_roles org_roles_root_org_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_roles
    ADD CONSTRAINT org_roles_root_org_id_foreign FOREIGN KEY (root_org_id) REFERENCES platform.organizations(id);


--
-- Name: org_scope_grant_branches org_scope_grant_branches_org_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_scope_grant_branches
    ADD CONSTRAINT org_scope_grant_branches_org_id_foreign FOREIGN KEY (org_id) REFERENCES platform.organizations(id);


--
-- Name: org_scope_grant_branches org_scope_grant_branches_scope_grant_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_scope_grant_branches
    ADD CONSTRAINT org_scope_grant_branches_scope_grant_id_foreign FOREIGN KEY (scope_grant_id) REFERENCES platform.org_scope_grants(id) ON DELETE CASCADE;


--
-- Name: org_scope_grants org_scope_grants_granted_by_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_scope_grants
    ADD CONSTRAINT org_scope_grants_granted_by_foreign FOREIGN KEY (granted_by) REFERENCES platform.users(id);


--
-- Name: org_scope_grants org_scope_grants_membership_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_scope_grants
    ADD CONSTRAINT org_scope_grants_membership_id_foreign FOREIGN KEY (membership_id) REFERENCES platform.org_memberships(id) ON DELETE CASCADE;


--
-- Name: org_scope_requests org_scope_requests_membership_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_scope_requests
    ADD CONSTRAINT org_scope_requests_membership_id_foreign FOREIGN KEY (membership_id) REFERENCES platform.org_memberships(id);


--
-- Name: org_scope_requests org_scope_requests_reviewed_by_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.org_scope_requests
    ADD CONSTRAINT org_scope_requests_reviewed_by_foreign FOREIGN KEY (reviewed_by) REFERENCES platform.users(id) ON DELETE SET NULL;


--
-- Name: organizations organizations_actor_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.organizations
    ADD CONSTRAINT organizations_actor_id_foreign FOREIGN KEY (actor_id) REFERENCES platform.actors(id);


--
-- Name: organizations organizations_approved_by_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.organizations
    ADD CONSTRAINT organizations_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES platform.users(id) ON DELETE SET NULL;


--
-- Name: organizations organizations_parent_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.organizations
    ADD CONSTRAINT organizations_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES platform.organizations(id) ON DELETE RESTRICT;


--
-- Name: organizations organizations_root_org_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.organizations
    ADD CONSTRAINT organizations_root_org_id_foreign FOREIGN KEY (root_org_id) REFERENCES platform.organizations(id) ON DELETE RESTRICT;


--
-- Name: platform_default_role_permissions platform_default_role_permissions_default_role_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_default_role_permissions
    ADD CONSTRAINT platform_default_role_permissions_default_role_id_foreign FOREIGN KEY (default_role_id) REFERENCES platform.platform_default_roles(id) ON DELETE CASCADE;


--
-- Name: platform_default_role_permissions platform_default_role_permissions_org_permission_def_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_default_role_permissions
    ADD CONSTRAINT platform_default_role_permissions_org_permission_def_id_foreign FOREIGN KEY (org_permission_def_id) REFERENCES platform.org_permission_definitions(id) ON DELETE CASCADE;


--
-- Name: platform_feature_flags platform_feature_flags_updated_by_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_feature_flags
    ADD CONSTRAINT platform_feature_flags_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES platform.users(id) ON DELETE SET NULL;


--
-- Name: platform_role_permissions platform_role_permissions_platform_permission_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_role_permissions
    ADD CONSTRAINT platform_role_permissions_platform_permission_id_foreign FOREIGN KEY (platform_permission_id) REFERENCES platform.platform_permissions(id) ON DELETE CASCADE;


--
-- Name: platform_role_permissions platform_role_permissions_platform_role_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.platform_role_permissions
    ADD CONSTRAINT platform_role_permissions_platform_role_id_foreign FOREIGN KEY (platform_role_id) REFERENCES platform.platform_roles(id) ON DELETE CASCADE;


--
-- Name: user_platform_roles user_platform_roles_granted_by_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.user_platform_roles
    ADD CONSTRAINT user_platform_roles_granted_by_foreign FOREIGN KEY (granted_by) REFERENCES platform.users(id) ON DELETE SET NULL;


--
-- Name: user_platform_roles user_platform_roles_platform_role_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.user_platform_roles
    ADD CONSTRAINT user_platform_roles_platform_role_id_foreign FOREIGN KEY (platform_role_id) REFERENCES platform.platform_roles(id) ON DELETE CASCADE;


--
-- Name: user_platform_roles user_platform_roles_user_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.user_platform_roles
    ADD CONSTRAINT user_platform_roles_user_id_foreign FOREIGN KEY (user_id) REFERENCES platform.users(id) ON DELETE CASCADE;


--
-- Name: user_social_logins user_social_logins_user_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.user_social_logins
    ADD CONSTRAINT user_social_logins_user_id_foreign FOREIGN KEY (user_id) REFERENCES platform.users(id) ON DELETE CASCADE;


--
-- Name: user_tier_assignments user_tier_assignments_assigned_by_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.user_tier_assignments
    ADD CONSTRAINT user_tier_assignments_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES platform.users(id) ON DELETE SET NULL;


--
-- Name: user_tier_assignments user_tier_assignments_tier_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.user_tier_assignments
    ADD CONSTRAINT user_tier_assignments_tier_id_foreign FOREIGN KEY (tier_id) REFERENCES platform.platform_tiers(id);


--
-- Name: user_tier_assignments user_tier_assignments_user_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.user_tier_assignments
    ADD CONSTRAINT user_tier_assignments_user_id_foreign FOREIGN KEY (user_id) REFERENCES platform.users(id) ON DELETE CASCADE;


--
-- Name: users users_actor_id_foreign; Type: FK CONSTRAINT; Schema: platform; Owner: postgres
--

ALTER TABLE ONLY platform.users
    ADD CONSTRAINT users_actor_id_foreign FOREIGN KEY (actor_id) REFERENCES platform.actors(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict fv7XplxeJH1UgINiZVLSdb0xpkkM34svaVOKi9pY1260efe0idsy1YDu10BcOiG

