--
-- PostgreSQL database dump
--

\restrict RHAA1eLFhrrvxW6mxs71TldCYyUydNaAERwyYB72MeigoMC7qFlUeqOH9CNMQhS

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
-- Name: commerce; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA commerce;


ALTER SCHEMA commerce OWNER TO postgres;

--
-- Name: communications; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA communications;


ALTER SCHEMA communications OWNER TO postgres;

--
-- Name: finance; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA finance;


ALTER SCHEMA finance OWNER TO postgres;

--
-- Name: inventory; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA inventory;


ALTER SCHEMA inventory OWNER TO postgres;

--
-- Name: logistics; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA logistics;


ALTER SCHEMA logistics OWNER TO postgres;

--
-- Name: notifications; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA notifications;


ALTER SCHEMA notifications OWNER TO postgres;

--
-- Name: pharma_marketing; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA pharma_marketing;


ALTER SCHEMA pharma_marketing OWNER TO postgres;

--
-- Name: platform; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA platform;


ALTER SCHEMA platform OWNER TO postgres;

--
-- Name: reporting; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA reporting;


ALTER SCHEMA reporting OWNER TO postgres;

--
-- Name: workflow; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA workflow;


ALTER SCHEMA workflow OWNER TO postgres;

--
-- Name: ltree; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS ltree WITH SCHEMA platform;


--
-- Name: EXTENSION ltree; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION ltree IS 'data type for hierarchical tree-like structures';


--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA platform;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: postgis; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis WITH SCHEMA pharma_marketing;


--
-- Name: EXTENSION postgis; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION postgis IS 'PostGIS geometry and geography spatial types and functions';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: basket_items; Type: TABLE; Schema: commerce; Owner: postgres
--

CREATE TABLE commerce.basket_items (
    id character(26) NOT NULL,
    basket_id character(26) NOT NULL,
    variant_id character(26) NOT NULL,
    seller_actor_id character(26) NOT NULL,
    quantity integer NOT NULL,
    unit_price numeric(15,4) NOT NULL,
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE commerce.basket_items OWNER TO postgres;

--
-- Name: baskets; Type: TABLE; Schema: commerce; Owner: postgres
--

CREATE TABLE commerce.baskets (
    id character(26) NOT NULL,
    buyer_actor_id character(26) NOT NULL,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    promotion_code character(26),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE commerce.baskets OWNER TO postgres;

--
-- Name: order_fulfillments; Type: TABLE; Schema: commerce; Owner: postgres
--

CREATE TABLE commerce.order_fulfillments (
    id character(26) NOT NULL,
    order_id character(26) NOT NULL,
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL,
    carrier character varying(100),
    tracking_number character varying(255),
    tracking_url character varying(500),
    weight_kg numeric(10,4),
    shipped_at timestamp(0) without time zone,
    delivered_at timestamp(0) without time zone,
    estimated_delivery_at timestamp(0) without time zone,
    notes text,
    metadata jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE commerce.order_fulfillments OWNER TO postgres;

--
-- Name: order_items; Type: TABLE; Schema: commerce; Owner: postgres
--

CREATE TABLE commerce.order_items (
    id character(26) NOT NULL,
    order_id character(26) NOT NULL,
    variant_id character(26) NOT NULL,
    product_id character(26) NOT NULL,
    product_name character varying(255) NOT NULL,
    variant_name character varying(255),
    sku character varying(150),
    quantity integer NOT NULL,
    unit_price numeric(15,4) NOT NULL,
    subtotal numeric(15,4) NOT NULL,
    discount_amount numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total numeric(15,4) NOT NULL,
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    reservation_id character(26),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE commerce.order_items OWNER TO postgres;

--
-- Name: order_returns; Type: TABLE; Schema: commerce; Owner: postgres
--

CREATE TABLE commerce.order_returns (
    id character(26) NOT NULL,
    order_id character(26) NOT NULL,
    requested_by character(26) NOT NULL,
    reason character varying(500) NOT NULL,
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL,
    resolution character varying(50),
    refund_amount numeric(15,4),
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    reviewed_by character(26),
    reviewed_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE commerce.order_returns OWNER TO postgres;

--
-- Name: orders; Type: TABLE; Schema: commerce; Owner: postgres
--

CREATE TABLE commerce.orders (
    id character(26) NOT NULL,
    order_number character varying(50) NOT NULL,
    basket_id character(26),
    buyer_actor_id character(26) NOT NULL,
    seller_actor_id character(26) NOT NULL,
    buyer_org_id character(26),
    seller_org_id character(26) NOT NULL,
    invoice_id character(26),
    payment_id character(26),
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL,
    subtotal numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    shipping_amount numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    tax_amount numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    discount_amount numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    shipping_rate_id character(26),
    shipping_address jsonb,
    billing_address jsonb,
    notes text,
    metadata jsonb,
    confirmed_at timestamp(0) without time zone,
    cancelled_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE commerce.orders OWNER TO postgres;

--
-- Name: product_attributes; Type: TABLE; Schema: commerce; Owner: postgres
--

CREATE TABLE commerce.product_attributes (
    id character(26) NOT NULL,
    variant_id character(26) NOT NULL,
    key character varying(100) NOT NULL,
    value character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE commerce.product_attributes OWNER TO postgres;

--
-- Name: product_bundles; Type: TABLE; Schema: commerce; Owner: postgres
--

CREATE TABLE commerce.product_bundles (
    id character(26) NOT NULL,
    bundle_product_id character(26) NOT NULL,
    component_variant_id character(26) NOT NULL,
    quantity integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE commerce.product_bundles OWNER TO postgres;

--
-- Name: product_variants; Type: TABLE; Schema: commerce; Owner: postgres
--

CREATE TABLE commerce.product_variants (
    id character(26) NOT NULL,
    product_id character(26) NOT NULL,
    sku character varying(150),
    name character varying(255),
    base_price numeric(15,4) NOT NULL,
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    weight_kg numeric(10,4),
    cost_price numeric(15,4),
    is_default boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE commerce.product_variants OWNER TO postgres;

--
-- Name: products; Type: TABLE; Schema: commerce; Owner: postgres
--

CREATE TABLE commerce.products (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    seller_actor_id character(26) NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255),
    description text,
    type character varying(50) DEFAULT 'physical'::character varying NOT NULL,
    status character varying(50) DEFAULT 'draft'::character varying NOT NULL,
    requires_confirmation boolean DEFAULT false NOT NULL,
    track_inventory boolean DEFAULT true NOT NULL,
    media jsonb,
    attributes jsonb,
    metadata jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE commerce.products OWNER TO postgres;

--
-- Name: shipping_rates; Type: TABLE; Schema: commerce; Owner: postgres
--

CREATE TABLE commerce.shipping_rates (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    name character varying(100) NOT NULL,
    method character varying(50) DEFAULT 'standard'::character varying NOT NULL,
    calculation_type character varying(50) NOT NULL,
    base_rate numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    rate_per_kg numeric(10,4),
    rate_per_value_percent numeric(8,4),
    free_shipping_threshold numeric(15,4),
    min_weight_kg numeric(10,4),
    max_weight_kg numeric(10,4),
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE commerce.shipping_rates OWNER TO postgres;

--
-- Name: actor_presence; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.actor_presence (
    actor_id character(26) NOT NULL,
    is_online boolean DEFAULT false NOT NULL,
    last_seen_at timestamp(0) without time zone,
    hide_last_seen boolean DEFAULT false NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE communications.actor_presence OWNER TO postgres;

--
-- Name: broadcast_messages; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.broadcast_messages (
    id character(26) NOT NULL,
    broadcast_id character(26) NOT NULL,
    sender_actor_id character(26) NOT NULL,
    content text,
    content_type character varying(50) DEFAULT 'text'::character varying NOT NULL,
    latitude numeric(10,7),
    longitude numeric(10,7),
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE communications.broadcast_messages OWNER TO postgres;

--
-- Name: broadcast_recipients; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.broadcast_recipients (
    id character(26) NOT NULL,
    broadcast_id character(26) NOT NULL,
    actor_id character(26) NOT NULL,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    added_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE communications.broadcast_recipients OWNER TO postgres;

--
-- Name: broadcasts; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.broadcasts (
    id character(26) NOT NULL,
    name character varying(255) NOT NULL,
    owner_actor_id character(26) NOT NULL,
    org_id character(26),
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE communications.broadcasts OWNER TO postgres;

--
-- Name: communities; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.communities (
    id character(26) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    avatar_url character varying(500),
    created_by character(26) NOT NULL,
    org_id character(26),
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    is_public boolean DEFAULT false NOT NULL,
    settings jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE communications.communities OWNER TO postgres;

--
-- Name: community_groups; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.community_groups (
    id character(26) NOT NULL,
    community_id character(26) NOT NULL,
    group_id character(26) NOT NULL,
    is_announcement_channel boolean DEFAULT false NOT NULL,
    added_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE communications.community_groups OWNER TO postgres;

--
-- Name: community_members; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.community_members (
    id character(26) NOT NULL,
    community_id character(26) NOT NULL,
    actor_id character(26) NOT NULL,
    role character varying(50) DEFAULT 'member'::character varying NOT NULL,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    joined_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    left_at timestamp(0) without time zone
);


ALTER TABLE communications.community_members OWNER TO postgres;

--
-- Name: direct_conversations; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.direct_conversations (
    id character(26) NOT NULL,
    initiator_actor_id character(26) NOT NULL,
    recipient_actor_id character(26) NOT NULL,
    last_message_id character(26),
    last_message_at timestamp(0) without time zone,
    initiator_archived boolean DEFAULT false NOT NULL,
    recipient_archived boolean DEFAULT false NOT NULL,
    initiator_muted boolean DEFAULT false NOT NULL,
    recipient_muted boolean DEFAULT false NOT NULL,
    retention_days integer DEFAULT 0 NOT NULL,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE communications.direct_conversations OWNER TO postgres;

--
-- Name: direct_messages; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.direct_messages (
    id character(26) NOT NULL,
    conversation_id character(26) NOT NULL,
    sender_actor_id character(26) NOT NULL,
    content text,
    content_type character varying(50) DEFAULT 'text'::character varying NOT NULL,
    reply_to_id character(26),
    forwarded_from_id character(26),
    forwarded boolean DEFAULT false NOT NULL,
    latitude numeric(10,7),
    longitude numeric(10,7),
    deleted_for_sender boolean DEFAULT false NOT NULL,
    deleted_for_recipient boolean DEFAULT false NOT NULL,
    deleted_for_everyone boolean DEFAULT false NOT NULL,
    deleted_at timestamp(0) without time zone,
    status character varying(50) DEFAULT 'sent'::character varying NOT NULL,
    delivered_at timestamp(0) without time zone,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE communications.direct_messages OWNER TO postgres;

--
-- Name: group_messages; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.group_messages (
    id character(26) NOT NULL,
    group_id character(26) NOT NULL,
    sender_actor_id character(26) NOT NULL,
    content text,
    content_type character varying(50) DEFAULT 'text'::character varying NOT NULL,
    reply_to_id character(26),
    forwarded_from_id character(26),
    forwarded boolean DEFAULT false NOT NULL,
    latitude numeric(10,7),
    longitude numeric(10,7),
    deleted_for_everyone boolean DEFAULT false NOT NULL,
    deleted_at timestamp(0) without time zone,
    is_system_message boolean DEFAULT false NOT NULL,
    system_event character varying(100),
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE communications.group_messages OWNER TO postgres;

--
-- Name: group_participants; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.group_participants (
    id character(26) NOT NULL,
    group_id character(26) NOT NULL,
    actor_id character(26) NOT NULL,
    role character varying(50) DEFAULT 'member'::character varying NOT NULL,
    muted boolean DEFAULT false NOT NULL,
    archived boolean DEFAULT false NOT NULL,
    muted_until timestamp(0) without time zone,
    added_by character(26),
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    joined_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    left_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE communications.group_participants OWNER TO postgres;

--
-- Name: groups; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.groups (
    id character(26) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    avatar_url character varying(500),
    created_by character(26) NOT NULL,
    org_id character(26),
    community_id character(26),
    type character varying(50) DEFAULT 'group'::character varying NOT NULL,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    max_participants integer DEFAULT 1024 NOT NULL,
    retention_days integer DEFAULT 0 NOT NULL,
    only_admins_can_message boolean DEFAULT false NOT NULL,
    only_admins_can_edit_info boolean DEFAULT true NOT NULL,
    last_message_id character(26),
    last_message_at timestamp(0) without time zone,
    settings jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE communications.groups OWNER TO postgres;

--
-- Name: message_attachments; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.message_attachments (
    id character(26) NOT NULL,
    message_type character varying(50) NOT NULL,
    message_id character(26) NOT NULL,
    type character varying(50) NOT NULL,
    file_name character varying(255),
    file_url character varying(1000) NOT NULL,
    mime_type character varying(100),
    file_size_bytes bigint,
    duration_seconds integer,
    width integer,
    height integer,
    thumbnail_url character varying(1000),
    metadata jsonb,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE communications.message_attachments OWNER TO postgres;

--
-- Name: message_reactions; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.message_reactions (
    id character(26) NOT NULL,
    message_type character varying(50) NOT NULL,
    message_id character(26) NOT NULL,
    actor_id character(26) NOT NULL,
    emoji character varying(10) NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE communications.message_reactions OWNER TO postgres;

--
-- Name: message_receipts; Type: TABLE; Schema: communications; Owner: postgres
--

CREATE TABLE communications.message_receipts (
    id character(26) NOT NULL,
    message_type character varying(50) NOT NULL,
    message_id character(26) NOT NULL,
    actor_id character(26) NOT NULL,
    delivered_at timestamp(0) without time zone,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE communications.message_receipts OWNER TO postgres;

--
-- Name: commission_configs; Type: TABLE; Schema: finance; Owner: postgres
--

CREATE TABLE finance.commission_configs (
    id character(26) NOT NULL,
    name character varying(100) DEFAULT 'default'::character varying NOT NULL,
    rate numeric(8,6) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    effective_from timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    effective_until timestamp(0) without time zone,
    created_by character(26),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE finance.commission_configs OWNER TO postgres;

--
-- Name: commission_records; Type: TABLE; Schema: finance; Owner: postgres
--

CREATE TABLE finance.commission_records (
    id character(26) NOT NULL,
    commission_config_id character(26) NOT NULL,
    payment_id character(26) NOT NULL,
    actor_id character(26) NOT NULL,
    transaction_amount numeric(15,4) NOT NULL,
    commission_rate numeric(8,6) NOT NULL,
    commission_amount numeric(15,4) NOT NULL,
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL,
    collected_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE finance.commission_records OWNER TO postgres;

--
-- Name: credit_accounts; Type: TABLE; Schema: finance; Owner: postgres
--

CREATE TABLE finance.credit_accounts (
    id character(26) NOT NULL,
    actor_id character(26) NOT NULL,
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE finance.credit_accounts OWNER TO postgres;

--
-- Name: credit_transactions; Type: TABLE; Schema: finance; Owner: postgres
--

CREATE TABLE finance.credit_transactions (
    id character(26) NOT NULL,
    account_id character(26) NOT NULL,
    amount numeric(15,4) NOT NULL,
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    type character varying(50) NOT NULL,
    description character varying(500),
    ref_type character varying(100),
    ref_id character(26),
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE finance.credit_transactions OWNER TO postgres;

--
-- Name: invoice_line_items; Type: TABLE; Schema: finance; Owner: postgres
--

CREATE TABLE finance.invoice_line_items (
    id character(26) NOT NULL,
    invoice_id character(26) NOT NULL,
    description character varying(500) NOT NULL,
    quantity integer DEFAULT 1 NOT NULL,
    unit_price numeric(15,4) NOT NULL,
    subtotal numeric(15,4) NOT NULL,
    tax_rate numeric(8,4) DEFAULT '0'::numeric NOT NULL,
    tax_amount numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    discount_amount numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total numeric(15,4) NOT NULL,
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    ref_type character varying(100),
    ref_id character(26),
    sort_order smallint DEFAULT '0'::smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE finance.invoice_line_items OWNER TO postgres;

--
-- Name: invoices; Type: TABLE; Schema: finance; Owner: postgres
--

CREATE TABLE finance.invoices (
    id character(26) NOT NULL,
    invoice_number character varying(50) NOT NULL,
    issuer_actor_id character(26) NOT NULL,
    recipient_actor_id character(26) NOT NULL,
    org_id character(26),
    source_type character varying(100),
    source_id character(26),
    status character varying(50) DEFAULT 'draft'::character varying NOT NULL,
    subtotal numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    tax_amount numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    discount_amount numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    issued_at timestamp(0) without time zone,
    due_at timestamp(0) without time zone,
    paid_at timestamp(0) without time zone,
    notes text,
    metadata jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE finance.invoices OWNER TO postgres;

--
-- Name: org_pricing_tiers; Type: TABLE; Schema: finance; Owner: postgres
--

CREATE TABLE finance.org_pricing_tiers (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    discount_percent numeric(8,4) DEFAULT '0'::numeric NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    sort_order smallint DEFAULT '0'::smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE finance.org_pricing_tiers OWNER TO postgres;

--
-- Name: org_subscriptions; Type: TABLE; Schema: finance; Owner: postgres
--

CREATE TABLE finance.org_subscriptions (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    plan_id character(26) NOT NULL,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    trial_ends_at timestamp(0) without time zone,
    current_period_start timestamp(0) without time zone NOT NULL,
    current_period_end timestamp(0) without time zone NOT NULL,
    cancelled_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    gateway_subscription_id character varying(255),
    gateway character varying(50),
    metadata jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE finance.org_subscriptions OWNER TO postgres;

--
-- Name: payments; Type: TABLE; Schema: finance; Owner: postgres
--

CREATE TABLE finance.payments (
    id character(26) NOT NULL,
    invoice_id character(26),
    payer_actor_id character(26) NOT NULL,
    payee_actor_id character(26) NOT NULL,
    amount numeric(15,4) NOT NULL,
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL,
    method character varying(50),
    gateway character varying(50),
    gateway_payment_id character varying(255),
    gateway_status character varying(100),
    gateway_fee numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    net_amount numeric(15,4),
    paid_at timestamp(0) without time zone,
    failure_reason text,
    metadata jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE finance.payments OWNER TO postgres;

--
-- Name: promotion_usages; Type: TABLE; Schema: finance; Owner: postgres
--

CREATE TABLE finance.promotion_usages (
    id character(26) NOT NULL,
    promotion_id character(26) NOT NULL,
    actor_id character(26) NOT NULL,
    ref_type character varying(100),
    ref_id character(26),
    discount_applied numeric(15,4) NOT NULL,
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    used_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE finance.promotion_usages OWNER TO postgres;

--
-- Name: promotions; Type: TABLE; Schema: finance; Owner: postgres
--

CREATE TABLE finance.promotions (
    id character(26) NOT NULL,
    org_id character(26),
    code character varying(50) NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    type character varying(50) NOT NULL,
    value numeric(15,4) NOT NULL,
    currency character(3),
    min_order_amount numeric(15,4),
    max_discount_amount numeric(15,4),
    usage_limit integer,
    usage_count integer DEFAULT 0 NOT NULL,
    usage_limit_per_actor integer,
    is_active boolean DEFAULT true NOT NULL,
    starts_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE finance.promotions OWNER TO postgres;

--
-- Name: subscription_plan_limits; Type: TABLE; Schema: finance; Owner: postgres
--

CREATE TABLE finance.subscription_plan_limits (
    id character(26) NOT NULL,
    plan_id character(26) NOT NULL,
    feature_key character varying(150) NOT NULL,
    feature_group character varying(100) NOT NULL,
    limit_value jsonb NOT NULL,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE finance.subscription_plan_limits OWNER TO postgres;

--
-- Name: subscription_plans; Type: TABLE; Schema: finance; Owner: postgres
--

CREATE TABLE finance.subscription_plans (
    id character(26) NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    price numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    billing_cycle character varying(20) DEFAULT 'monthly'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    is_public boolean DEFAULT true NOT NULL,
    sort_order smallint DEFAULT '0'::smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE finance.subscription_plans OWNER TO postgres;

--
-- Name: inventory_batches; Type: TABLE; Schema: inventory; Owner: postgres
--

CREATE TABLE inventory.inventory_batches (
    id character(26) NOT NULL,
    warehouse_id character(26) NOT NULL,
    product_id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    batch_number character varying(100),
    sku character varying(100),
    quantity_received integer DEFAULT 0 NOT NULL,
    quantity_available integer DEFAULT 0 NOT NULL,
    quantity_reserved integer DEFAULT 0 NOT NULL,
    quantity_damaged integer DEFAULT 0 NOT NULL,
    unit_cost numeric(15,4),
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    received_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    expires_at timestamp(0) without time zone,
    best_before_at timestamp(0) without time zone,
    metadata jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE inventory.inventory_batches OWNER TO postgres;

--
-- Name: stock_alerts; Type: TABLE; Schema: inventory; Owner: postgres
--

CREATE TABLE inventory.stock_alerts (
    id character(26) NOT NULL,
    warehouse_id character(26),
    product_id character(26),
    batch_id character(26),
    org_id character(26) NOT NULL,
    type character varying(50) NOT NULL,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    threshold integer,
    current_value integer,
    message text,
    acknowledged_by character(26),
    acknowledged_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE inventory.stock_alerts OWNER TO postgres;

--
-- Name: stock_movements; Type: TABLE; Schema: inventory; Owner: postgres
--

CREATE TABLE inventory.stock_movements (
    id character(26) NOT NULL,
    batch_id character(26) NOT NULL,
    warehouse_id character(26) NOT NULL,
    product_id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    type character varying(50) NOT NULL,
    quantity integer NOT NULL,
    quantity_before integer NOT NULL,
    quantity_after integer NOT NULL,
    ref_type character varying(100),
    ref_id character(26),
    performed_by character(26),
    notes text,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE inventory.stock_movements OWNER TO postgres;

--
-- Name: stock_reservations; Type: TABLE; Schema: inventory; Owner: postgres
--

CREATE TABLE inventory.stock_reservations (
    id character(26) NOT NULL,
    batch_id character(26) NOT NULL,
    product_id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    quantity integer NOT NULL,
    ref_type character varying(100),
    ref_id character(26),
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE inventory.stock_reservations OWNER TO postgres;

--
-- Name: warehouses; Type: TABLE; Schema: inventory; Owner: postgres
--

CREATE TABLE inventory.warehouses (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    actor_id character(26),
    name character varying(255) NOT NULL,
    code character varying(50),
    type character varying(50) DEFAULT 'standard'::character varying NOT NULL,
    address text,
    city character varying(100),
    country character varying(100),
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    settings jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE inventory.warehouses OWNER TO postgres;

--
-- Name: lg_courier_accounts; Type: TABLE; Schema: logistics; Owner: postgres
--

CREATE TABLE logistics.lg_courier_accounts (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    courier character varying(50) NOT NULL,
    name character varying(100) NOT NULL,
    account_number character varying(100),
    api_key_encrypted text,
    api_secret_encrypted text,
    settings jsonb,
    is_active boolean DEFAULT true NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE logistics.lg_courier_accounts OWNER TO postgres;

--
-- Name: lg_courier_shipments; Type: TABLE; Schema: logistics; Owner: postgres
--

CREATE TABLE logistics.lg_courier_shipments (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    courier_account_id character(26) NOT NULL,
    order_id character(26),
    order_number character varying(50),
    tracking_number character varying(255),
    waybill_number character varying(255),
    tracking_url character varying(1000),
    status character varying(50) DEFAULT 'booked'::character varying NOT NULL,
    courier_status character varying(100),
    weight_kg numeric(10,4),
    unit_count integer DEFAULT 1 NOT NULL,
    declared_value numeric(15,4),
    shipping_cost numeric(15,4),
    currency character(3) DEFAULT 'KES'::bpchar NOT NULL,
    recipient_name character varying(255),
    recipient_phone character varying(30),
    delivery_address text,
    booked_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    picked_up_at timestamp(0) without time zone,
    delivered_at timestamp(0) without time zone,
    failed_at timestamp(0) without time zone,
    estimated_delivery_at timestamp(0) without time zone,
    failure_reason text,
    courier_events jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE logistics.lg_courier_shipments OWNER TO postgres;

--
-- Name: lg_delivery_proofs; Type: TABLE; Schema: logistics; Owner: postgres
--

CREATE TABLE logistics.lg_delivery_proofs (
    id character(26) NOT NULL,
    stop_id character(26) NOT NULL,
    photo_url character varying(1000),
    photo_latitude numeric(10,7),
    photo_longitude numeric(10,7),
    signature_url character varying(1000),
    signed_by_name character varying(255),
    confirmation_code character varying(20),
    code_confirmed_at timestamp(0) without time zone,
    captured_by character(26),
    captured_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE logistics.lg_delivery_proofs OWNER TO postgres;

--
-- Name: lg_delivery_rates; Type: TABLE; Schema: logistics; Owner: postgres
--

CREATE TABLE logistics.lg_delivery_rates (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    zone_id character(26),
    name character varying(100) NOT NULL,
    base_rate numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    rate_per_unit numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    rate_per_kg numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    min_charge numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    max_charge numeric(15,4),
    currency character(3) DEFAULT 'KES'::bpchar NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE logistics.lg_delivery_rates OWNER TO postgres;

--
-- Name: lg_delivery_runs; Type: TABLE; Schema: logistics; Owner: postgres
--

CREATE TABLE logistics.lg_delivery_runs (
    id character(26) NOT NULL,
    run_number character varying(50) NOT NULL,
    org_id character(26) NOT NULL,
    driver_id character(26),
    vehicle_id character(26),
    dispatched_by character(26),
    status character varying(50) DEFAULT 'draft'::character varying NOT NULL,
    scheduled_date date NOT NULL,
    scheduled_start_time time(0) without time zone,
    dispatched_at timestamp(0) without time zone,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    total_stops integer DEFAULT 0 NOT NULL,
    delivered_count integer DEFAULT 0 NOT NULL,
    failed_count integer DEFAULT 0 NOT NULL,
    notes text,
    metadata jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE logistics.lg_delivery_runs OWNER TO postgres;

--
-- Name: lg_delivery_stops; Type: TABLE; Schema: logistics; Owner: postgres
--

CREATE TABLE logistics.lg_delivery_stops (
    id character(26) NOT NULL,
    run_id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    order_id character(26),
    order_number character varying(50),
    recipient_name character varying(255) NOT NULL,
    recipient_phone character varying(30),
    address text NOT NULL,
    city character varying(100),
    zone_id character(26),
    latitude numeric(10,7),
    longitude numeric(10,7),
    stop_sequence integer DEFAULT 0 NOT NULL,
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL,
    unit_count integer DEFAULT 1 NOT NULL,
    weight_kg numeric(10,4),
    rate_id character(26),
    delivery_cost numeric(15,4),
    currency character(3) DEFAULT 'KES'::bpchar NOT NULL,
    estimated_arrival_at timestamp(0) without time zone,
    arrived_at timestamp(0) without time zone,
    delivered_at timestamp(0) without time zone,
    failed_at timestamp(0) without time zone,
    failure_reason character varying(50),
    failure_notes text,
    rescheduled_date date,
    return_status character varying(50),
    returned_at timestamp(0) without time zone,
    delivery_latitude numeric(10,7),
    delivery_longitude numeric(10,7),
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE logistics.lg_delivery_stops OWNER TO postgres;

--
-- Name: lg_delivery_zones; Type: TABLE; Schema: logistics; Owner: postgres
--

CREATE TABLE logistics.lg_delivery_zones (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    name character varying(100) NOT NULL,
    code character varying(20),
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE logistics.lg_delivery_zones OWNER TO postgres;

--
-- Name: lg_drivers; Type: TABLE; Schema: logistics; Owner: postgres
--

CREATE TABLE logistics.lg_drivers (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    actor_id character(26) NOT NULL,
    name character varying(255) NOT NULL,
    phone character varying(30),
    license_number character varying(100),
    license_expiry date,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    availability character varying(50) DEFAULT 'offline'::character varying NOT NULL,
    last_seen_at timestamp(0) without time zone,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE logistics.lg_drivers OWNER TO postgres;

--
-- Name: lg_stop_status_logs; Type: TABLE; Schema: logistics; Owner: postgres
--

CREATE TABLE logistics.lg_stop_status_logs (
    id character(26) NOT NULL,
    stop_id character(26) NOT NULL,
    from_status character varying(50),
    to_status character varying(50) NOT NULL,
    changed_by character(26),
    latitude numeric(10,7),
    longitude numeric(10,7),
    notes text,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE logistics.lg_stop_status_logs OWNER TO postgres;

--
-- Name: lg_vehicles; Type: TABLE; Schema: logistics; Owner: postgres
--

CREATE TABLE logistics.lg_vehicles (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    registration character varying(50) NOT NULL,
    type character varying(50) DEFAULT 'van'::character varying NOT NULL,
    make character varying(100),
    model character varying(100),
    year integer,
    payload_kg numeric(10,2),
    max_stops integer,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE logistics.lg_vehicles OWNER TO postgres;

--
-- Name: device_tokens; Type: TABLE; Schema: notifications; Owner: postgres
--

CREATE TABLE notifications.device_tokens (
    id character(26) NOT NULL,
    actor_id character(26) NOT NULL,
    token character varying(500) NOT NULL,
    platform character varying(50) NOT NULL,
    driver character varying(50) DEFAULT 'fcm'::character varying NOT NULL,
    device_name character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    last_used_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE notifications.device_tokens OWNER TO postgres;

--
-- Name: notification_preferences; Type: TABLE; Schema: notifications; Owner: postgres
--

CREATE TABLE notifications.notification_preferences (
    id character(26) NOT NULL,
    actor_id character(26) NOT NULL,
    type character varying(100) NOT NULL,
    push_enabled boolean DEFAULT true NOT NULL,
    email_enabled boolean DEFAULT false NOT NULL,
    sms_enabled boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE notifications.notification_preferences OWNER TO postgres;

--
-- Name: notifications; Type: TABLE; Schema: notifications; Owner: postgres
--

CREATE TABLE notifications.notifications (
    id character(26) NOT NULL,
    actor_id character(26) NOT NULL,
    type character varying(100) NOT NULL,
    title character varying(255) NOT NULL,
    body text NOT NULL,
    channel character varying(50) DEFAULT 'push'::character varying NOT NULL,
    action_url character varying(500),
    ref_type character varying(100),
    ref_id character(26),
    data jsonb,
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL,
    sent_at timestamp(0) without time zone,
    delivered_at timestamp(0) without time zone,
    read_at timestamp(0) without time zone,
    retry_count integer DEFAULT 0 NOT NULL,
    failure_reason text,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE notifications.notifications OWNER TO postgres;

--
-- Name: workflow_definitions; Type: TABLE; Schema: notifications; Owner: postgres
--

CREATE TABLE notifications.workflow_definitions (
    id character(26) NOT NULL,
    org_id character(26),
    name character varying(255) NOT NULL,
    description text,
    trigger_event character varying(150) NOT NULL,
    module character varying(100) NOT NULL,
    steps jsonb NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE notifications.workflow_definitions OWNER TO postgres;

--
-- Name: workflow_runs; Type: TABLE; Schema: notifications; Owner: postgres
--

CREATE TABLE notifications.workflow_runs (
    id character(26) NOT NULL,
    workflow_definition_id character(26) NOT NULL,
    trigger_event character varying(150) NOT NULL,
    trigger_payload jsonb NOT NULL,
    status character varying(50) DEFAULT 'running'::character varying NOT NULL,
    current_step integer DEFAULT 0 NOT NULL,
    context jsonb,
    failure_reason text,
    started_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE notifications.workflow_runs OWNER TO postgres;

--
-- Name: workflow_step_logs; Type: TABLE; Schema: notifications; Owner: postgres
--

CREATE TABLE notifications.workflow_step_logs (
    id character(26) NOT NULL,
    run_id character(26) NOT NULL,
    step_index integer NOT NULL,
    step_type character varying(100) NOT NULL,
    step_name character varying(255),
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL,
    input jsonb,
    output jsonb,
    error text,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE notifications.workflow_step_logs OWNER TO postgres;

--
-- Name: pm_customer_contacts; Type: TABLE; Schema: pharma_marketing; Owner: postgres
--

CREATE TABLE pharma_marketing.pm_customer_contacts (
    id character(26) NOT NULL,
    customer_id character(26) NOT NULL,
    name character varying(255) NOT NULL,
    role character varying(100),
    phone character varying(30),
    email character varying(255),
    whatsapp_number character varying(30),
    is_primary boolean DEFAULT false NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE pharma_marketing.pm_customer_contacts OWNER TO postgres;

--
-- Name: pm_customers; Type: TABLE; Schema: pharma_marketing; Owner: postgres
--

CREATE TABLE pharma_marketing.pm_customers (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    assigned_officer_id character(26),
    customer_type character varying(50) DEFAULT 'b2b'::character varying NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(100),
    category character varying(100),
    tier character varying(50) DEFAULT 'standard'::character varying NOT NULL,
    status character varying(50) DEFAULT 'active'::character varying NOT NULL,
    business_registration character varying(100),
    tax_pin character varying(100),
    address text,
    city character varying(100),
    county character varying(100),
    country character varying(100) DEFAULT 'Kenya'::character varying NOT NULL,
    latitude numeric(10,7),
    longitude numeric(10,7),
    gps_accuracy_meters integer,
    phone character varying(30),
    alt_phone character varying(30),
    email character varying(255),
    whatsapp_number character varying(30),
    receives_whatsapp boolean DEFAULT true NOT NULL,
    receives_sms boolean DEFAULT true NOT NULL,
    receives_in_app boolean DEFAULT true NOT NULL,
    credit_limit numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    currency character(3) DEFAULT 'KES'::bpchar NOT NULL,
    notes text,
    metadata jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE pharma_marketing.pm_customers OWNER TO postgres;

--
-- Name: pm_daily_reports; Type: TABLE; Schema: pharma_marketing; Owner: postgres
--

CREATE TABLE pharma_marketing.pm_daily_reports (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    officer_actor_id character(26) NOT NULL,
    report_date date NOT NULL,
    status character varying(50) DEFAULT 'draft'::character varying NOT NULL,
    planned_visits integer DEFAULT 0 NOT NULL,
    completed_visits integer DEFAULT 0 NOT NULL,
    new_customers integer DEFAULT 0 NOT NULL,
    samples_distributed integer DEFAULT 0 NOT NULL,
    summary text,
    challenges text,
    achievements text,
    next_day_plan text,
    reviewed_by character(26),
    submitted_at timestamp(0) without time zone,
    reviewed_at timestamp(0) without time zone,
    review_notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE pharma_marketing.pm_daily_reports OWNER TO postgres;

--
-- Name: pm_field_visits; Type: TABLE; Schema: pharma_marketing; Owner: postgres
--

CREATE TABLE pharma_marketing.pm_field_visits (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    customer_id character(26) NOT NULL,
    officer_actor_id character(26) NOT NULL,
    weekly_plan_item_id character(26),
    status character varying(50) DEFAULT 'in_progress'::character varying NOT NULL,
    visit_type character varying(50) DEFAULT 'routine'::character varying NOT NULL,
    check_in_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    check_out_at timestamp(0) without time zone,
    duration_minutes integer,
    check_in_latitude numeric(10,7),
    check_in_longitude numeric(10,7),
    check_in_gps_accuracy_meters integer,
    check_out_latitude numeric(10,7),
    check_out_longitude numeric(10,7),
    objective text,
    discussion_summary text,
    outcome text,
    outcome_status character varying(50),
    follow_up_notes text,
    follow_up_date date,
    contact_person_id character(26),
    contact_person_name character varying(255),
    notes text,
    metadata jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE pharma_marketing.pm_field_visits OWNER TO postgres;

--
-- Name: pm_product_update_deliveries; Type: TABLE; Schema: pharma_marketing; Owner: postgres
--

CREATE TABLE pharma_marketing.pm_product_update_deliveries (
    id character(26) NOT NULL,
    product_update_id character(26) NOT NULL,
    customer_id character(26) NOT NULL,
    channel character varying(50) NOT NULL,
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL,
    recipient_address character varying(255),
    external_message_id character varying(255),
    failure_reason text,
    retry_count integer DEFAULT 0 NOT NULL,
    sent_at timestamp(0) without time zone,
    delivered_at timestamp(0) without time zone,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE pharma_marketing.pm_product_update_deliveries OWNER TO postgres;

--
-- Name: pm_product_updates; Type: TABLE; Schema: pharma_marketing; Owner: postgres
--

CREATE TABLE pharma_marketing.pm_product_updates (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    created_by character(26) NOT NULL,
    title character varying(255) NOT NULL,
    body text NOT NULL,
    update_type character varying(50) DEFAULT 'general'::character varying NOT NULL,
    target_segment character varying(50) DEFAULT 'all'::character varying NOT NULL,
    target_filters jsonb,
    send_in_app boolean DEFAULT true NOT NULL,
    send_whatsapp boolean DEFAULT true NOT NULL,
    send_sms boolean DEFAULT false NOT NULL,
    product_ids jsonb,
    media_url character varying(1000),
    media_type character varying(50),
    status character varying(50) DEFAULT 'draft'::character varying NOT NULL,
    scheduled_at timestamp(0) without time zone,
    sent_at timestamp(0) without time zone,
    total_recipients integer DEFAULT 0 NOT NULL,
    sent_count integer DEFAULT 0 NOT NULL,
    failed_count integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE pharma_marketing.pm_product_updates OWNER TO postgres;

--
-- Name: pm_visit_attachments; Type: TABLE; Schema: pharma_marketing; Owner: postgres
--

CREATE TABLE pharma_marketing.pm_visit_attachments (
    id character(26) NOT NULL,
    visit_id character(26) NOT NULL,
    uploaded_by character(26) NOT NULL,
    type character varying(50) NOT NULL,
    file_name character varying(255) NOT NULL,
    file_url character varying(1000) NOT NULL,
    mime_type character varying(100),
    file_size_bytes bigint,
    width integer,
    height integer,
    caption text,
    latitude numeric(10,7),
    longitude numeric(10,7),
    metadata jsonb,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE pharma_marketing.pm_visit_attachments OWNER TO postgres;

--
-- Name: pm_visit_products; Type: TABLE; Schema: pharma_marketing; Owner: postgres
--

CREATE TABLE pharma_marketing.pm_visit_products (
    id character(26) NOT NULL,
    visit_id character(26) NOT NULL,
    product_id character(26) NOT NULL,
    product_name character varying(255) NOT NULL,
    action character varying(50) DEFAULT 'promoted'::character varying NOT NULL,
    samples_given integer DEFAULT 0 NOT NULL,
    customer_feedback text,
    notes text,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE pharma_marketing.pm_visit_products OWNER TO postgres;

--
-- Name: pm_weekly_plan_items; Type: TABLE; Schema: pharma_marketing; Owner: postgres
--

CREATE TABLE pharma_marketing.pm_weekly_plan_items (
    id character(26) NOT NULL,
    plan_id character(26) NOT NULL,
    planned_date date NOT NULL,
    item_type character varying(50) DEFAULT 'customer_visit'::character varying NOT NULL,
    customer_id character(26),
    customer_name character varying(255),
    title character varying(255),
    objective text,
    planned_start_time time(0) without time zone,
    planned_end_time time(0) without time zone,
    sort_order integer DEFAULT 0 NOT NULL,
    status character varying(50) DEFAULT 'planned'::character varying NOT NULL,
    visit_id character(26),
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE pharma_marketing.pm_weekly_plan_items OWNER TO postgres;

--
-- Name: pm_weekly_plans; Type: TABLE; Schema: pharma_marketing; Owner: postgres
--

CREATE TABLE pharma_marketing.pm_weekly_plans (
    id character(26) NOT NULL,
    org_id character(26) NOT NULL,
    officer_actor_id character(26) NOT NULL,
    week_start_date date NOT NULL,
    week_end_date date NOT NULL,
    status character varying(50) DEFAULT 'draft'::character varying NOT NULL,
    objectives text,
    notes text,
    approved_by character(26),
    submitted_at timestamp(0) without time zone,
    approved_at timestamp(0) without time zone,
    rejected_at timestamp(0) without time zone,
    rejection_reason text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE pharma_marketing.pm_weekly_plans OWNER TO postgres;

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
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.jobs OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


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
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: basket_items basket_items_basket_id_variant_id_unique; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.basket_items
    ADD CONSTRAINT basket_items_basket_id_variant_id_unique UNIQUE (basket_id, variant_id);


--
-- Name: basket_items basket_items_pkey; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.basket_items
    ADD CONSTRAINT basket_items_pkey PRIMARY KEY (id);


--
-- Name: baskets baskets_buyer_actor_id_unique; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.baskets
    ADD CONSTRAINT baskets_buyer_actor_id_unique UNIQUE (buyer_actor_id);


--
-- Name: baskets baskets_pkey; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.baskets
    ADD CONSTRAINT baskets_pkey PRIMARY KEY (id);


--
-- Name: order_fulfillments order_fulfillments_pkey; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.order_fulfillments
    ADD CONSTRAINT order_fulfillments_pkey PRIMARY KEY (id);


--
-- Name: order_items order_items_pkey; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.order_items
    ADD CONSTRAINT order_items_pkey PRIMARY KEY (id);


--
-- Name: order_returns order_returns_pkey; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.order_returns
    ADD CONSTRAINT order_returns_pkey PRIMARY KEY (id);


--
-- Name: orders orders_order_number_unique; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.orders
    ADD CONSTRAINT orders_order_number_unique UNIQUE (order_number);


--
-- Name: orders orders_pkey; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.orders
    ADD CONSTRAINT orders_pkey PRIMARY KEY (id);


--
-- Name: product_attributes product_attributes_pkey; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.product_attributes
    ADD CONSTRAINT product_attributes_pkey PRIMARY KEY (id);


--
-- Name: product_attributes product_attributes_variant_id_key_unique; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.product_attributes
    ADD CONSTRAINT product_attributes_variant_id_key_unique UNIQUE (variant_id, key);


--
-- Name: product_bundles product_bundles_pkey; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.product_bundles
    ADD CONSTRAINT product_bundles_pkey PRIMARY KEY (id);


--
-- Name: product_variants product_variants_pkey; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.product_variants
    ADD CONSTRAINT product_variants_pkey PRIMARY KEY (id);


--
-- Name: products products_org_id_slug_unique; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.products
    ADD CONSTRAINT products_org_id_slug_unique UNIQUE (org_id, slug);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: shipping_rates shipping_rates_pkey; Type: CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.shipping_rates
    ADD CONSTRAINT shipping_rates_pkey PRIMARY KEY (id);


--
-- Name: actor_presence actor_presence_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.actor_presence
    ADD CONSTRAINT actor_presence_pkey PRIMARY KEY (actor_id);


--
-- Name: broadcast_messages broadcast_messages_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.broadcast_messages
    ADD CONSTRAINT broadcast_messages_pkey PRIMARY KEY (id);


--
-- Name: broadcast_recipients broadcast_recipients_broadcast_id_actor_id_unique; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.broadcast_recipients
    ADD CONSTRAINT broadcast_recipients_broadcast_id_actor_id_unique UNIQUE (broadcast_id, actor_id);


--
-- Name: broadcast_recipients broadcast_recipients_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.broadcast_recipients
    ADD CONSTRAINT broadcast_recipients_pkey PRIMARY KEY (id);


--
-- Name: broadcasts broadcasts_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.broadcasts
    ADD CONSTRAINT broadcasts_pkey PRIMARY KEY (id);


--
-- Name: communities communities_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.communities
    ADD CONSTRAINT communities_pkey PRIMARY KEY (id);


--
-- Name: community_groups community_groups_community_id_group_id_unique; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.community_groups
    ADD CONSTRAINT community_groups_community_id_group_id_unique UNIQUE (community_id, group_id);


--
-- Name: community_groups community_groups_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.community_groups
    ADD CONSTRAINT community_groups_pkey PRIMARY KEY (id);


--
-- Name: community_members community_members_community_id_actor_id_unique; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.community_members
    ADD CONSTRAINT community_members_community_id_actor_id_unique UNIQUE (community_id, actor_id);


--
-- Name: community_members community_members_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.community_members
    ADD CONSTRAINT community_members_pkey PRIMARY KEY (id);


--
-- Name: direct_conversations direct_conversations_initiator_actor_id_recipient_actor_id_uniq; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.direct_conversations
    ADD CONSTRAINT direct_conversations_initiator_actor_id_recipient_actor_id_uniq UNIQUE (initiator_actor_id, recipient_actor_id);


--
-- Name: direct_conversations direct_conversations_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.direct_conversations
    ADD CONSTRAINT direct_conversations_pkey PRIMARY KEY (id);


--
-- Name: direct_messages direct_messages_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.direct_messages
    ADD CONSTRAINT direct_messages_pkey PRIMARY KEY (id);


--
-- Name: group_messages group_messages_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.group_messages
    ADD CONSTRAINT group_messages_pkey PRIMARY KEY (id);


--
-- Name: group_participants group_participants_group_id_actor_id_unique; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.group_participants
    ADD CONSTRAINT group_participants_group_id_actor_id_unique UNIQUE (group_id, actor_id);


--
-- Name: group_participants group_participants_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.group_participants
    ADD CONSTRAINT group_participants_pkey PRIMARY KEY (id);


--
-- Name: groups groups_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (id);


--
-- Name: message_attachments message_attachments_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.message_attachments
    ADD CONSTRAINT message_attachments_pkey PRIMARY KEY (id);


--
-- Name: message_reactions message_reactions_message_type_message_id_actor_id_unique; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.message_reactions
    ADD CONSTRAINT message_reactions_message_type_message_id_actor_id_unique UNIQUE (message_type, message_id, actor_id);


--
-- Name: message_reactions message_reactions_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.message_reactions
    ADD CONSTRAINT message_reactions_pkey PRIMARY KEY (id);


--
-- Name: message_receipts message_receipts_message_type_message_id_actor_id_unique; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.message_receipts
    ADD CONSTRAINT message_receipts_message_type_message_id_actor_id_unique UNIQUE (message_type, message_id, actor_id);


--
-- Name: message_receipts message_receipts_pkey; Type: CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.message_receipts
    ADD CONSTRAINT message_receipts_pkey PRIMARY KEY (id);


--
-- Name: commission_configs commission_configs_pkey; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.commission_configs
    ADD CONSTRAINT commission_configs_pkey PRIMARY KEY (id);


--
-- Name: commission_records commission_records_pkey; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.commission_records
    ADD CONSTRAINT commission_records_pkey PRIMARY KEY (id);


--
-- Name: credit_accounts credit_accounts_actor_id_unique; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.credit_accounts
    ADD CONSTRAINT credit_accounts_actor_id_unique UNIQUE (actor_id);


--
-- Name: credit_accounts credit_accounts_pkey; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.credit_accounts
    ADD CONSTRAINT credit_accounts_pkey PRIMARY KEY (id);


--
-- Name: credit_transactions credit_transactions_pkey; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.credit_transactions
    ADD CONSTRAINT credit_transactions_pkey PRIMARY KEY (id);


--
-- Name: invoice_line_items invoice_line_items_pkey; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.invoice_line_items
    ADD CONSTRAINT invoice_line_items_pkey PRIMARY KEY (id);


--
-- Name: invoices invoices_invoice_number_unique; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.invoices
    ADD CONSTRAINT invoices_invoice_number_unique UNIQUE (invoice_number);


--
-- Name: invoices invoices_pkey; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.invoices
    ADD CONSTRAINT invoices_pkey PRIMARY KEY (id);


--
-- Name: org_pricing_tiers org_pricing_tiers_org_id_name_unique; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.org_pricing_tiers
    ADD CONSTRAINT org_pricing_tiers_org_id_name_unique UNIQUE (org_id, name);


--
-- Name: org_pricing_tiers org_pricing_tiers_pkey; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.org_pricing_tiers
    ADD CONSTRAINT org_pricing_tiers_pkey PRIMARY KEY (id);


--
-- Name: org_subscriptions org_subscriptions_org_id_unique; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.org_subscriptions
    ADD CONSTRAINT org_subscriptions_org_id_unique UNIQUE (org_id);


--
-- Name: org_subscriptions org_subscriptions_pkey; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.org_subscriptions
    ADD CONSTRAINT org_subscriptions_pkey PRIMARY KEY (id);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (id);


--
-- Name: promotion_usages promotion_usages_pkey; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.promotion_usages
    ADD CONSTRAINT promotion_usages_pkey PRIMARY KEY (id);


--
-- Name: promotions promotions_code_unique; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.promotions
    ADD CONSTRAINT promotions_code_unique UNIQUE (code);


--
-- Name: promotions promotions_pkey; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.promotions
    ADD CONSTRAINT promotions_pkey PRIMARY KEY (id);


--
-- Name: subscription_plan_limits subscription_plan_limits_pkey; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.subscription_plan_limits
    ADD CONSTRAINT subscription_plan_limits_pkey PRIMARY KEY (id);


--
-- Name: subscription_plan_limits subscription_plan_limits_plan_id_feature_key_unique; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.subscription_plan_limits
    ADD CONSTRAINT subscription_plan_limits_plan_id_feature_key_unique UNIQUE (plan_id, feature_key);


--
-- Name: subscription_plans subscription_plans_name_unique; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.subscription_plans
    ADD CONSTRAINT subscription_plans_name_unique UNIQUE (name);


--
-- Name: subscription_plans subscription_plans_pkey; Type: CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.subscription_plans
    ADD CONSTRAINT subscription_plans_pkey PRIMARY KEY (id);


--
-- Name: inventory_batches inventory_batches_pkey; Type: CONSTRAINT; Schema: inventory; Owner: postgres
--

ALTER TABLE ONLY inventory.inventory_batches
    ADD CONSTRAINT inventory_batches_pkey PRIMARY KEY (id);


--
-- Name: stock_alerts stock_alerts_pkey; Type: CONSTRAINT; Schema: inventory; Owner: postgres
--

ALTER TABLE ONLY inventory.stock_alerts
    ADD CONSTRAINT stock_alerts_pkey PRIMARY KEY (id);


--
-- Name: stock_movements stock_movements_pkey; Type: CONSTRAINT; Schema: inventory; Owner: postgres
--

ALTER TABLE ONLY inventory.stock_movements
    ADD CONSTRAINT stock_movements_pkey PRIMARY KEY (id);


--
-- Name: stock_reservations stock_reservations_pkey; Type: CONSTRAINT; Schema: inventory; Owner: postgres
--

ALTER TABLE ONLY inventory.stock_reservations
    ADD CONSTRAINT stock_reservations_pkey PRIMARY KEY (id);


--
-- Name: warehouses warehouses_pkey; Type: CONSTRAINT; Schema: inventory; Owner: postgres
--

ALTER TABLE ONLY inventory.warehouses
    ADD CONSTRAINT warehouses_pkey PRIMARY KEY (id);


--
-- Name: lg_courier_accounts lg_courier_accounts_pkey; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_courier_accounts
    ADD CONSTRAINT lg_courier_accounts_pkey PRIMARY KEY (id);


--
-- Name: lg_courier_shipments lg_courier_shipments_pkey; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_courier_shipments
    ADD CONSTRAINT lg_courier_shipments_pkey PRIMARY KEY (id);


--
-- Name: lg_delivery_proofs lg_delivery_proofs_pkey; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_delivery_proofs
    ADD CONSTRAINT lg_delivery_proofs_pkey PRIMARY KEY (id);


--
-- Name: lg_delivery_proofs lg_delivery_proofs_stop_id_unique; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_delivery_proofs
    ADD CONSTRAINT lg_delivery_proofs_stop_id_unique UNIQUE (stop_id);


--
-- Name: lg_delivery_rates lg_delivery_rates_pkey; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_delivery_rates
    ADD CONSTRAINT lg_delivery_rates_pkey PRIMARY KEY (id);


--
-- Name: lg_delivery_runs lg_delivery_runs_pkey; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_delivery_runs
    ADD CONSTRAINT lg_delivery_runs_pkey PRIMARY KEY (id);


--
-- Name: lg_delivery_runs lg_delivery_runs_run_number_unique; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_delivery_runs
    ADD CONSTRAINT lg_delivery_runs_run_number_unique UNIQUE (run_number);


--
-- Name: lg_delivery_stops lg_delivery_stops_pkey; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_delivery_stops
    ADD CONSTRAINT lg_delivery_stops_pkey PRIMARY KEY (id);


--
-- Name: lg_delivery_zones lg_delivery_zones_org_id_code_unique; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_delivery_zones
    ADD CONSTRAINT lg_delivery_zones_org_id_code_unique UNIQUE (org_id, code);


--
-- Name: lg_delivery_zones lg_delivery_zones_pkey; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_delivery_zones
    ADD CONSTRAINT lg_delivery_zones_pkey PRIMARY KEY (id);


--
-- Name: lg_drivers lg_drivers_actor_id_unique; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_drivers
    ADD CONSTRAINT lg_drivers_actor_id_unique UNIQUE (actor_id);


--
-- Name: lg_drivers lg_drivers_pkey; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_drivers
    ADD CONSTRAINT lg_drivers_pkey PRIMARY KEY (id);


--
-- Name: lg_stop_status_logs lg_stop_status_logs_pkey; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_stop_status_logs
    ADD CONSTRAINT lg_stop_status_logs_pkey PRIMARY KEY (id);


--
-- Name: lg_vehicles lg_vehicles_pkey; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_vehicles
    ADD CONSTRAINT lg_vehicles_pkey PRIMARY KEY (id);


--
-- Name: lg_vehicles lg_vehicles_registration_unique; Type: CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_vehicles
    ADD CONSTRAINT lg_vehicles_registration_unique UNIQUE (registration);


--
-- Name: device_tokens device_tokens_pkey; Type: CONSTRAINT; Schema: notifications; Owner: postgres
--

ALTER TABLE ONLY notifications.device_tokens
    ADD CONSTRAINT device_tokens_pkey PRIMARY KEY (id);


--
-- Name: device_tokens device_tokens_token_unique; Type: CONSTRAINT; Schema: notifications; Owner: postgres
--

ALTER TABLE ONLY notifications.device_tokens
    ADD CONSTRAINT device_tokens_token_unique UNIQUE (token);


--
-- Name: notification_preferences notification_preferences_actor_id_type_unique; Type: CONSTRAINT; Schema: notifications; Owner: postgres
--

ALTER TABLE ONLY notifications.notification_preferences
    ADD CONSTRAINT notification_preferences_actor_id_type_unique UNIQUE (actor_id, type);


--
-- Name: notification_preferences notification_preferences_pkey; Type: CONSTRAINT; Schema: notifications; Owner: postgres
--

ALTER TABLE ONLY notifications.notification_preferences
    ADD CONSTRAINT notification_preferences_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: notifications; Owner: postgres
--

ALTER TABLE ONLY notifications.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: workflow_definitions workflow_definitions_pkey; Type: CONSTRAINT; Schema: notifications; Owner: postgres
--

ALTER TABLE ONLY notifications.workflow_definitions
    ADD CONSTRAINT workflow_definitions_pkey PRIMARY KEY (id);


--
-- Name: workflow_runs workflow_runs_pkey; Type: CONSTRAINT; Schema: notifications; Owner: postgres
--

ALTER TABLE ONLY notifications.workflow_runs
    ADD CONSTRAINT workflow_runs_pkey PRIMARY KEY (id);


--
-- Name: workflow_step_logs workflow_step_logs_pkey; Type: CONSTRAINT; Schema: notifications; Owner: postgres
--

ALTER TABLE ONLY notifications.workflow_step_logs
    ADD CONSTRAINT workflow_step_logs_pkey PRIMARY KEY (id);


--
-- Name: pm_customer_contacts pm_customer_contacts_pkey; Type: CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_customer_contacts
    ADD CONSTRAINT pm_customer_contacts_pkey PRIMARY KEY (id);


--
-- Name: pm_customers pm_customers_org_id_code_unique; Type: CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_customers
    ADD CONSTRAINT pm_customers_org_id_code_unique UNIQUE (org_id, code);


--
-- Name: pm_customers pm_customers_pkey; Type: CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_customers
    ADD CONSTRAINT pm_customers_pkey PRIMARY KEY (id);


--
-- Name: pm_daily_reports pm_daily_reports_officer_actor_id_report_date_unique; Type: CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_daily_reports
    ADD CONSTRAINT pm_daily_reports_officer_actor_id_report_date_unique UNIQUE (officer_actor_id, report_date);


--
-- Name: pm_daily_reports pm_daily_reports_pkey; Type: CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_daily_reports
    ADD CONSTRAINT pm_daily_reports_pkey PRIMARY KEY (id);


--
-- Name: pm_field_visits pm_field_visits_pkey; Type: CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_field_visits
    ADD CONSTRAINT pm_field_visits_pkey PRIMARY KEY (id);


--
-- Name: pm_product_update_deliveries pm_product_update_deliveries_pkey; Type: CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_product_update_deliveries
    ADD CONSTRAINT pm_product_update_deliveries_pkey PRIMARY KEY (id);


--
-- Name: pm_product_updates pm_product_updates_pkey; Type: CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_product_updates
    ADD CONSTRAINT pm_product_updates_pkey PRIMARY KEY (id);


--
-- Name: pm_visit_attachments pm_visit_attachments_pkey; Type: CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_visit_attachments
    ADD CONSTRAINT pm_visit_attachments_pkey PRIMARY KEY (id);


--
-- Name: pm_visit_products pm_visit_products_pkey; Type: CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_visit_products
    ADD CONSTRAINT pm_visit_products_pkey PRIMARY KEY (id);


--
-- Name: pm_weekly_plan_items pm_weekly_plan_items_pkey; Type: CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_weekly_plan_items
    ADD CONSTRAINT pm_weekly_plan_items_pkey PRIMARY KEY (id);


--
-- Name: pm_weekly_plans pm_weekly_plans_officer_actor_id_week_start_date_unique; Type: CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_weekly_plans
    ADD CONSTRAINT pm_weekly_plans_officer_actor_id_week_start_date_unique UNIQUE (officer_actor_id, week_start_date);


--
-- Name: pm_weekly_plans pm_weekly_plans_pkey; Type: CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_weekly_plans
    ADD CONSTRAINT pm_weekly_plans_pkey PRIMARY KEY (id);


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
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: basket_items_basket_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX basket_items_basket_id_index ON commerce.basket_items USING btree (basket_id);


--
-- Name: baskets_buyer_actor_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX baskets_buyer_actor_id_index ON commerce.baskets USING btree (buyer_actor_id);


--
-- Name: baskets_status_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX baskets_status_index ON commerce.baskets USING btree (status);


--
-- Name: order_fulfillments_order_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX order_fulfillments_order_id_index ON commerce.order_fulfillments USING btree (order_id);


--
-- Name: order_items_order_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX order_items_order_id_index ON commerce.order_items USING btree (order_id);


--
-- Name: order_items_variant_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX order_items_variant_id_index ON commerce.order_items USING btree (variant_id);


--
-- Name: order_returns_order_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX order_returns_order_id_index ON commerce.order_returns USING btree (order_id);


--
-- Name: order_returns_status_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX order_returns_status_index ON commerce.order_returns USING btree (status);


--
-- Name: orders_basket_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX orders_basket_id_index ON commerce.orders USING btree (basket_id);


--
-- Name: orders_buyer_actor_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX orders_buyer_actor_id_index ON commerce.orders USING btree (buyer_actor_id);


--
-- Name: orders_seller_actor_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX orders_seller_actor_id_index ON commerce.orders USING btree (seller_actor_id);


--
-- Name: orders_seller_org_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX orders_seller_org_id_index ON commerce.orders USING btree (seller_org_id);


--
-- Name: orders_status_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX orders_status_index ON commerce.orders USING btree (status);


--
-- Name: product_attributes_variant_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX product_attributes_variant_id_index ON commerce.product_attributes USING btree (variant_id);


--
-- Name: product_bundles_bundle_product_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX product_bundles_bundle_product_id_index ON commerce.product_bundles USING btree (bundle_product_id);


--
-- Name: product_variants_product_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX product_variants_product_id_index ON commerce.product_variants USING btree (product_id);


--
-- Name: product_variants_sku_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX product_variants_sku_index ON commerce.product_variants USING btree (sku);


--
-- Name: products_org_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX products_org_id_index ON commerce.products USING btree (org_id);


--
-- Name: products_seller_actor_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX products_seller_actor_id_index ON commerce.products USING btree (seller_actor_id);


--
-- Name: products_status_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX products_status_index ON commerce.products USING btree (status);


--
-- Name: products_type_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX products_type_index ON commerce.products USING btree (type);


--
-- Name: shipping_rates_org_id_index; Type: INDEX; Schema: commerce; Owner: postgres
--

CREATE INDEX shipping_rates_org_id_index ON commerce.shipping_rates USING btree (org_id);


--
-- Name: broadcast_messages_broadcast_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX broadcast_messages_broadcast_id_index ON communications.broadcast_messages USING btree (broadcast_id);


--
-- Name: broadcast_messages_created_at_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX broadcast_messages_created_at_index ON communications.broadcast_messages USING btree (created_at);


--
-- Name: broadcast_recipients_broadcast_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX broadcast_recipients_broadcast_id_index ON communications.broadcast_recipients USING btree (broadcast_id);


--
-- Name: broadcasts_org_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX broadcasts_org_id_index ON communications.broadcasts USING btree (org_id);


--
-- Name: broadcasts_owner_actor_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX broadcasts_owner_actor_id_index ON communications.broadcasts USING btree (owner_actor_id);


--
-- Name: communities_is_public_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX communities_is_public_index ON communications.communities USING btree (is_public);


--
-- Name: communities_org_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX communities_org_id_index ON communications.communities USING btree (org_id);


--
-- Name: community_groups_community_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX community_groups_community_id_index ON communications.community_groups USING btree (community_id);


--
-- Name: community_members_community_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX community_members_community_id_index ON communications.community_members USING btree (community_id);


--
-- Name: direct_conversations_initiator_actor_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX direct_conversations_initiator_actor_id_index ON communications.direct_conversations USING btree (initiator_actor_id);


--
-- Name: direct_conversations_last_message_at_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX direct_conversations_last_message_at_index ON communications.direct_conversations USING btree (last_message_at);


--
-- Name: direct_conversations_recipient_actor_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX direct_conversations_recipient_actor_id_index ON communications.direct_conversations USING btree (recipient_actor_id);


--
-- Name: direct_messages_conversation_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX direct_messages_conversation_id_index ON communications.direct_messages USING btree (conversation_id);


--
-- Name: direct_messages_created_at_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX direct_messages_created_at_index ON communications.direct_messages USING btree (created_at);


--
-- Name: direct_messages_sender_actor_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX direct_messages_sender_actor_id_index ON communications.direct_messages USING btree (sender_actor_id);


--
-- Name: group_messages_created_at_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX group_messages_created_at_index ON communications.group_messages USING btree (created_at);


--
-- Name: group_messages_group_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX group_messages_group_id_index ON communications.group_messages USING btree (group_id);


--
-- Name: group_messages_sender_actor_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX group_messages_sender_actor_id_index ON communications.group_messages USING btree (sender_actor_id);


--
-- Name: group_participants_actor_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX group_participants_actor_id_index ON communications.group_participants USING btree (actor_id);


--
-- Name: group_participants_group_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX group_participants_group_id_index ON communications.group_participants USING btree (group_id);


--
-- Name: groups_community_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX groups_community_id_index ON communications.groups USING btree (community_id);


--
-- Name: groups_org_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX groups_org_id_index ON communications.groups USING btree (org_id);


--
-- Name: groups_status_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX groups_status_index ON communications.groups USING btree (status);


--
-- Name: message_attachments_message_type_message_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX message_attachments_message_type_message_id_index ON communications.message_attachments USING btree (message_type, message_id);


--
-- Name: message_reactions_message_type_message_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX message_reactions_message_type_message_id_index ON communications.message_reactions USING btree (message_type, message_id);


--
-- Name: message_receipts_actor_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX message_receipts_actor_id_index ON communications.message_receipts USING btree (actor_id);


--
-- Name: message_receipts_message_type_message_id_index; Type: INDEX; Schema: communications; Owner: postgres
--

CREATE INDEX message_receipts_message_type_message_id_index ON communications.message_receipts USING btree (message_type, message_id);


--
-- Name: commission_configs_is_active_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX commission_configs_is_active_index ON finance.commission_configs USING btree (is_active);


--
-- Name: commission_configs_is_default_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX commission_configs_is_default_index ON finance.commission_configs USING btree (is_default);


--
-- Name: commission_records_actor_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX commission_records_actor_id_index ON finance.commission_records USING btree (actor_id);


--
-- Name: commission_records_payment_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX commission_records_payment_id_index ON finance.commission_records USING btree (payment_id);


--
-- Name: commission_records_status_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX commission_records_status_index ON finance.commission_records USING btree (status);


--
-- Name: credit_accounts_actor_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX credit_accounts_actor_id_index ON finance.credit_accounts USING btree (actor_id);


--
-- Name: credit_transactions_account_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX credit_transactions_account_id_index ON finance.credit_transactions USING btree (account_id);


--
-- Name: credit_transactions_created_at_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX credit_transactions_created_at_index ON finance.credit_transactions USING btree (created_at);


--
-- Name: credit_transactions_ref_type_ref_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX credit_transactions_ref_type_ref_id_index ON finance.credit_transactions USING btree (ref_type, ref_id);


--
-- Name: credit_transactions_type_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX credit_transactions_type_index ON finance.credit_transactions USING btree (type);


--
-- Name: invoice_line_items_invoice_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX invoice_line_items_invoice_id_index ON finance.invoice_line_items USING btree (invoice_id);


--
-- Name: invoices_issuer_actor_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX invoices_issuer_actor_id_index ON finance.invoices USING btree (issuer_actor_id);


--
-- Name: invoices_org_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX invoices_org_id_index ON finance.invoices USING btree (org_id);


--
-- Name: invoices_recipient_actor_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX invoices_recipient_actor_id_index ON finance.invoices USING btree (recipient_actor_id);


--
-- Name: invoices_source_type_source_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX invoices_source_type_source_id_index ON finance.invoices USING btree (source_type, source_id);


--
-- Name: invoices_status_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX invoices_status_index ON finance.invoices USING btree (status);


--
-- Name: org_pricing_tiers_org_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX org_pricing_tiers_org_id_index ON finance.org_pricing_tiers USING btree (org_id);


--
-- Name: org_subscriptions_org_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX org_subscriptions_org_id_index ON finance.org_subscriptions USING btree (org_id);


--
-- Name: org_subscriptions_status_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX org_subscriptions_status_index ON finance.org_subscriptions USING btree (status);


--
-- Name: payments_gateway_payment_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX payments_gateway_payment_id_index ON finance.payments USING btree (gateway_payment_id);


--
-- Name: payments_invoice_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX payments_invoice_id_index ON finance.payments USING btree (invoice_id);


--
-- Name: payments_payee_actor_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX payments_payee_actor_id_index ON finance.payments USING btree (payee_actor_id);


--
-- Name: payments_payer_actor_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX payments_payer_actor_id_index ON finance.payments USING btree (payer_actor_id);


--
-- Name: payments_status_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX payments_status_index ON finance.payments USING btree (status);


--
-- Name: promotion_usages_actor_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX promotion_usages_actor_id_index ON finance.promotion_usages USING btree (actor_id);


--
-- Name: promotion_usages_promotion_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX promotion_usages_promotion_id_index ON finance.promotion_usages USING btree (promotion_id);


--
-- Name: promotion_usages_ref_type_ref_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX promotion_usages_ref_type_ref_id_index ON finance.promotion_usages USING btree (ref_type, ref_id);


--
-- Name: promotions_code_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX promotions_code_index ON finance.promotions USING btree (code);


--
-- Name: promotions_is_active_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX promotions_is_active_index ON finance.promotions USING btree (is_active);


--
-- Name: promotions_org_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX promotions_org_id_index ON finance.promotions USING btree (org_id);


--
-- Name: subscription_plan_limits_plan_id_index; Type: INDEX; Schema: finance; Owner: postgres
--

CREATE INDEX subscription_plan_limits_plan_id_index ON finance.subscription_plan_limits USING btree (plan_id);


--
-- Name: inventory_batches_batch_number_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX inventory_batches_batch_number_index ON inventory.inventory_batches USING btree (batch_number);


--
-- Name: inventory_batches_expires_at_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX inventory_batches_expires_at_index ON inventory.inventory_batches USING btree (expires_at);


--
-- Name: inventory_batches_org_id_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX inventory_batches_org_id_index ON inventory.inventory_batches USING btree (org_id);


--
-- Name: inventory_batches_product_id_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX inventory_batches_product_id_index ON inventory.inventory_batches USING btree (product_id);


--
-- Name: inventory_batches_status_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX inventory_batches_status_index ON inventory.inventory_batches USING btree (status);


--
-- Name: inventory_batches_warehouse_id_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX inventory_batches_warehouse_id_index ON inventory.inventory_batches USING btree (warehouse_id);


--
-- Name: stock_alerts_org_id_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX stock_alerts_org_id_index ON inventory.stock_alerts USING btree (org_id);


--
-- Name: stock_alerts_product_id_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX stock_alerts_product_id_index ON inventory.stock_alerts USING btree (product_id);


--
-- Name: stock_alerts_type_status_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX stock_alerts_type_status_index ON inventory.stock_alerts USING btree (type, status);


--
-- Name: stock_movements_batch_id_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX stock_movements_batch_id_index ON inventory.stock_movements USING btree (batch_id);


--
-- Name: stock_movements_created_at_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX stock_movements_created_at_index ON inventory.stock_movements USING btree (created_at);


--
-- Name: stock_movements_org_id_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX stock_movements_org_id_index ON inventory.stock_movements USING btree (org_id);


--
-- Name: stock_movements_product_id_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX stock_movements_product_id_index ON inventory.stock_movements USING btree (product_id);


--
-- Name: stock_movements_ref_type_ref_id_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX stock_movements_ref_type_ref_id_index ON inventory.stock_movements USING btree (ref_type, ref_id);


--
-- Name: stock_movements_type_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX stock_movements_type_index ON inventory.stock_movements USING btree (type);


--
-- Name: stock_reservations_batch_id_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX stock_reservations_batch_id_index ON inventory.stock_reservations USING btree (batch_id);


--
-- Name: stock_reservations_product_id_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX stock_reservations_product_id_index ON inventory.stock_reservations USING btree (product_id);


--
-- Name: stock_reservations_ref_type_ref_id_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX stock_reservations_ref_type_ref_id_index ON inventory.stock_reservations USING btree (ref_type, ref_id);


--
-- Name: stock_reservations_status_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX stock_reservations_status_index ON inventory.stock_reservations USING btree (status);


--
-- Name: warehouses_org_id_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX warehouses_org_id_index ON inventory.warehouses USING btree (org_id);


--
-- Name: warehouses_status_index; Type: INDEX; Schema: inventory; Owner: postgres
--

CREATE INDEX warehouses_status_index ON inventory.warehouses USING btree (status);


--
-- Name: lg_courier_accounts_org_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_courier_accounts_org_id_index ON logistics.lg_courier_accounts USING btree (org_id);


--
-- Name: lg_courier_shipments_order_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_courier_shipments_order_id_index ON logistics.lg_courier_shipments USING btree (order_id);


--
-- Name: lg_courier_shipments_org_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_courier_shipments_org_id_index ON logistics.lg_courier_shipments USING btree (org_id);


--
-- Name: lg_courier_shipments_status_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_courier_shipments_status_index ON logistics.lg_courier_shipments USING btree (status);


--
-- Name: lg_courier_shipments_tracking_number_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_courier_shipments_tracking_number_index ON logistics.lg_courier_shipments USING btree (tracking_number);


--
-- Name: lg_delivery_rates_org_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_delivery_rates_org_id_index ON logistics.lg_delivery_rates USING btree (org_id);


--
-- Name: lg_delivery_rates_zone_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_delivery_rates_zone_id_index ON logistics.lg_delivery_rates USING btree (zone_id);


--
-- Name: lg_delivery_runs_driver_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_delivery_runs_driver_id_index ON logistics.lg_delivery_runs USING btree (driver_id);


--
-- Name: lg_delivery_runs_org_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_delivery_runs_org_id_index ON logistics.lg_delivery_runs USING btree (org_id);


--
-- Name: lg_delivery_runs_scheduled_date_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_delivery_runs_scheduled_date_index ON logistics.lg_delivery_runs USING btree (scheduled_date);


--
-- Name: lg_delivery_runs_status_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_delivery_runs_status_index ON logistics.lg_delivery_runs USING btree (status);


--
-- Name: lg_delivery_stops_order_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_delivery_stops_order_id_index ON logistics.lg_delivery_stops USING btree (order_id);


--
-- Name: lg_delivery_stops_org_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_delivery_stops_org_id_index ON logistics.lg_delivery_stops USING btree (org_id);


--
-- Name: lg_delivery_stops_run_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_delivery_stops_run_id_index ON logistics.lg_delivery_stops USING btree (run_id);


--
-- Name: lg_delivery_stops_status_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_delivery_stops_status_index ON logistics.lg_delivery_stops USING btree (status);


--
-- Name: lg_delivery_stops_stop_sequence_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_delivery_stops_stop_sequence_index ON logistics.lg_delivery_stops USING btree (stop_sequence);


--
-- Name: lg_delivery_zones_org_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_delivery_zones_org_id_index ON logistics.lg_delivery_zones USING btree (org_id);


--
-- Name: lg_drivers_actor_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_drivers_actor_id_index ON logistics.lg_drivers USING btree (actor_id);


--
-- Name: lg_drivers_availability_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_drivers_availability_index ON logistics.lg_drivers USING btree (availability);


--
-- Name: lg_drivers_org_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_drivers_org_id_index ON logistics.lg_drivers USING btree (org_id);


--
-- Name: lg_stop_status_logs_created_at_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_stop_status_logs_created_at_index ON logistics.lg_stop_status_logs USING btree (created_at);


--
-- Name: lg_stop_status_logs_stop_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_stop_status_logs_stop_id_index ON logistics.lg_stop_status_logs USING btree (stop_id);


--
-- Name: lg_vehicles_org_id_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_vehicles_org_id_index ON logistics.lg_vehicles USING btree (org_id);


--
-- Name: lg_vehicles_status_index; Type: INDEX; Schema: logistics; Owner: postgres
--

CREATE INDEX lg_vehicles_status_index ON logistics.lg_vehicles USING btree (status);


--
-- Name: device_tokens_actor_id_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX device_tokens_actor_id_index ON notifications.device_tokens USING btree (actor_id);


--
-- Name: device_tokens_is_active_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX device_tokens_is_active_index ON notifications.device_tokens USING btree (is_active);


--
-- Name: notification_preferences_actor_id_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX notification_preferences_actor_id_index ON notifications.notification_preferences USING btree (actor_id);


--
-- Name: notifications_actor_id_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX notifications_actor_id_index ON notifications.notifications USING btree (actor_id);


--
-- Name: notifications_created_at_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX notifications_created_at_index ON notifications.notifications USING btree (created_at);


--
-- Name: notifications_ref_type_ref_id_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX notifications_ref_type_ref_id_index ON notifications.notifications USING btree (ref_type, ref_id);


--
-- Name: notifications_status_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX notifications_status_index ON notifications.notifications USING btree (status);


--
-- Name: notifications_type_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX notifications_type_index ON notifications.notifications USING btree (type);


--
-- Name: workflow_definitions_is_active_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX workflow_definitions_is_active_index ON notifications.workflow_definitions USING btree (is_active);


--
-- Name: workflow_definitions_org_id_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX workflow_definitions_org_id_index ON notifications.workflow_definitions USING btree (org_id);


--
-- Name: workflow_definitions_trigger_event_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX workflow_definitions_trigger_event_index ON notifications.workflow_definitions USING btree (trigger_event);


--
-- Name: workflow_runs_status_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX workflow_runs_status_index ON notifications.workflow_runs USING btree (status);


--
-- Name: workflow_runs_trigger_event_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX workflow_runs_trigger_event_index ON notifications.workflow_runs USING btree (trigger_event);


--
-- Name: workflow_runs_workflow_definition_id_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX workflow_runs_workflow_definition_id_index ON notifications.workflow_runs USING btree (workflow_definition_id);


--
-- Name: workflow_step_logs_run_id_index; Type: INDEX; Schema: notifications; Owner: postgres
--

CREATE INDEX workflow_step_logs_run_id_index ON notifications.workflow_step_logs USING btree (run_id);


--
-- Name: pm_customer_contacts_customer_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_customer_contacts_customer_id_index ON pharma_marketing.pm_customer_contacts USING btree (customer_id);


--
-- Name: pm_customers_assigned_officer_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_customers_assigned_officer_id_index ON pharma_marketing.pm_customers USING btree (assigned_officer_id);


--
-- Name: pm_customers_category_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_customers_category_index ON pharma_marketing.pm_customers USING btree (category);


--
-- Name: pm_customers_customer_type_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_customers_customer_type_index ON pharma_marketing.pm_customers USING btree (customer_type);


--
-- Name: pm_customers_org_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_customers_org_id_index ON pharma_marketing.pm_customers USING btree (org_id);


--
-- Name: pm_customers_status_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_customers_status_index ON pharma_marketing.pm_customers USING btree (status);


--
-- Name: pm_customers_tier_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_customers_tier_index ON pharma_marketing.pm_customers USING btree (tier);


--
-- Name: pm_daily_reports_officer_actor_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_daily_reports_officer_actor_id_index ON pharma_marketing.pm_daily_reports USING btree (officer_actor_id);


--
-- Name: pm_daily_reports_org_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_daily_reports_org_id_index ON pharma_marketing.pm_daily_reports USING btree (org_id);


--
-- Name: pm_daily_reports_report_date_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_daily_reports_report_date_index ON pharma_marketing.pm_daily_reports USING btree (report_date);


--
-- Name: pm_daily_reports_status_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_daily_reports_status_index ON pharma_marketing.pm_daily_reports USING btree (status);


--
-- Name: pm_field_visits_check_in_at_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_field_visits_check_in_at_index ON pharma_marketing.pm_field_visits USING btree (check_in_at);


--
-- Name: pm_field_visits_customer_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_field_visits_customer_id_index ON pharma_marketing.pm_field_visits USING btree (customer_id);


--
-- Name: pm_field_visits_officer_actor_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_field_visits_officer_actor_id_index ON pharma_marketing.pm_field_visits USING btree (officer_actor_id);


--
-- Name: pm_field_visits_org_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_field_visits_org_id_index ON pharma_marketing.pm_field_visits USING btree (org_id);


--
-- Name: pm_field_visits_status_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_field_visits_status_index ON pharma_marketing.pm_field_visits USING btree (status);


--
-- Name: pm_field_visits_weekly_plan_item_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_field_visits_weekly_plan_item_id_index ON pharma_marketing.pm_field_visits USING btree (weekly_plan_item_id);


--
-- Name: pm_product_update_deliveries_customer_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_product_update_deliveries_customer_id_index ON pharma_marketing.pm_product_update_deliveries USING btree (customer_id);


--
-- Name: pm_product_update_deliveries_product_update_id_channel_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_product_update_deliveries_product_update_id_channel_index ON pharma_marketing.pm_product_update_deliveries USING btree (product_update_id, channel);


--
-- Name: pm_product_update_deliveries_product_update_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_product_update_deliveries_product_update_id_index ON pharma_marketing.pm_product_update_deliveries USING btree (product_update_id);


--
-- Name: pm_product_update_deliveries_status_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_product_update_deliveries_status_index ON pharma_marketing.pm_product_update_deliveries USING btree (status);


--
-- Name: pm_product_updates_org_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_product_updates_org_id_index ON pharma_marketing.pm_product_updates USING btree (org_id);


--
-- Name: pm_product_updates_scheduled_at_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_product_updates_scheduled_at_index ON pharma_marketing.pm_product_updates USING btree (scheduled_at);


--
-- Name: pm_product_updates_status_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_product_updates_status_index ON pharma_marketing.pm_product_updates USING btree (status);


--
-- Name: pm_visit_attachments_visit_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_visit_attachments_visit_id_index ON pharma_marketing.pm_visit_attachments USING btree (visit_id);


--
-- Name: pm_visit_products_product_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_visit_products_product_id_index ON pharma_marketing.pm_visit_products USING btree (product_id);


--
-- Name: pm_visit_products_visit_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_visit_products_visit_id_index ON pharma_marketing.pm_visit_products USING btree (visit_id);


--
-- Name: pm_weekly_plan_items_customer_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_weekly_plan_items_customer_id_index ON pharma_marketing.pm_weekly_plan_items USING btree (customer_id);


--
-- Name: pm_weekly_plan_items_plan_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_weekly_plan_items_plan_id_index ON pharma_marketing.pm_weekly_plan_items USING btree (plan_id);


--
-- Name: pm_weekly_plan_items_planned_date_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_weekly_plan_items_planned_date_index ON pharma_marketing.pm_weekly_plan_items USING btree (planned_date);


--
-- Name: pm_weekly_plans_officer_actor_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_weekly_plans_officer_actor_id_index ON pharma_marketing.pm_weekly_plans USING btree (officer_actor_id);


--
-- Name: pm_weekly_plans_org_id_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_weekly_plans_org_id_index ON pharma_marketing.pm_weekly_plans USING btree (org_id);


--
-- Name: pm_weekly_plans_status_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_weekly_plans_status_index ON pharma_marketing.pm_weekly_plans USING btree (status);


--
-- Name: pm_weekly_plans_week_start_date_index; Type: INDEX; Schema: pharma_marketing; Owner: postgres
--

CREATE INDEX pm_weekly_plans_week_start_date_index ON pharma_marketing.pm_weekly_plans USING btree (week_start_date);


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
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: basket_items basket_items_basket_id_foreign; Type: FK CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.basket_items
    ADD CONSTRAINT basket_items_basket_id_foreign FOREIGN KEY (basket_id) REFERENCES commerce.baskets(id) ON DELETE CASCADE;


--
-- Name: basket_items basket_items_variant_id_foreign; Type: FK CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.basket_items
    ADD CONSTRAINT basket_items_variant_id_foreign FOREIGN KEY (variant_id) REFERENCES commerce.product_variants(id) ON DELETE RESTRICT;


--
-- Name: order_fulfillments order_fulfillments_order_id_foreign; Type: FK CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.order_fulfillments
    ADD CONSTRAINT order_fulfillments_order_id_foreign FOREIGN KEY (order_id) REFERENCES commerce.orders(id) ON DELETE CASCADE;


--
-- Name: order_items order_items_order_id_foreign; Type: FK CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.order_items
    ADD CONSTRAINT order_items_order_id_foreign FOREIGN KEY (order_id) REFERENCES commerce.orders(id) ON DELETE CASCADE;


--
-- Name: order_returns order_returns_order_id_foreign; Type: FK CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.order_returns
    ADD CONSTRAINT order_returns_order_id_foreign FOREIGN KEY (order_id) REFERENCES commerce.orders(id) ON DELETE RESTRICT;


--
-- Name: product_attributes product_attributes_variant_id_foreign; Type: FK CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.product_attributes
    ADD CONSTRAINT product_attributes_variant_id_foreign FOREIGN KEY (variant_id) REFERENCES commerce.product_variants(id) ON DELETE CASCADE;


--
-- Name: product_bundles product_bundles_bundle_product_id_foreign; Type: FK CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.product_bundles
    ADD CONSTRAINT product_bundles_bundle_product_id_foreign FOREIGN KEY (bundle_product_id) REFERENCES commerce.products(id) ON DELETE CASCADE;


--
-- Name: product_bundles product_bundles_component_variant_id_foreign; Type: FK CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.product_bundles
    ADD CONSTRAINT product_bundles_component_variant_id_foreign FOREIGN KEY (component_variant_id) REFERENCES commerce.product_variants(id) ON DELETE RESTRICT;


--
-- Name: product_variants product_variants_product_id_foreign; Type: FK CONSTRAINT; Schema: commerce; Owner: postgres
--

ALTER TABLE ONLY commerce.product_variants
    ADD CONSTRAINT product_variants_product_id_foreign FOREIGN KEY (product_id) REFERENCES commerce.products(id) ON DELETE CASCADE;


--
-- Name: broadcast_messages broadcast_messages_broadcast_id_foreign; Type: FK CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.broadcast_messages
    ADD CONSTRAINT broadcast_messages_broadcast_id_foreign FOREIGN KEY (broadcast_id) REFERENCES communications.broadcasts(id) ON DELETE CASCADE;


--
-- Name: broadcast_recipients broadcast_recipients_broadcast_id_foreign; Type: FK CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.broadcast_recipients
    ADD CONSTRAINT broadcast_recipients_broadcast_id_foreign FOREIGN KEY (broadcast_id) REFERENCES communications.broadcasts(id) ON DELETE CASCADE;


--
-- Name: community_groups community_groups_community_id_foreign; Type: FK CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.community_groups
    ADD CONSTRAINT community_groups_community_id_foreign FOREIGN KEY (community_id) REFERENCES communications.communities(id) ON DELETE CASCADE;


--
-- Name: community_groups community_groups_group_id_foreign; Type: FK CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.community_groups
    ADD CONSTRAINT community_groups_group_id_foreign FOREIGN KEY (group_id) REFERENCES communications.groups(id) ON DELETE CASCADE;


--
-- Name: community_members community_members_community_id_foreign; Type: FK CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.community_members
    ADD CONSTRAINT community_members_community_id_foreign FOREIGN KEY (community_id) REFERENCES communications.communities(id) ON DELETE CASCADE;


--
-- Name: direct_messages direct_messages_conversation_id_foreign; Type: FK CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.direct_messages
    ADD CONSTRAINT direct_messages_conversation_id_foreign FOREIGN KEY (conversation_id) REFERENCES communications.direct_conversations(id) ON DELETE CASCADE;


--
-- Name: group_messages group_messages_group_id_foreign; Type: FK CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.group_messages
    ADD CONSTRAINT group_messages_group_id_foreign FOREIGN KEY (group_id) REFERENCES communications.groups(id) ON DELETE CASCADE;


--
-- Name: group_participants group_participants_group_id_foreign; Type: FK CONSTRAINT; Schema: communications; Owner: postgres
--

ALTER TABLE ONLY communications.group_participants
    ADD CONSTRAINT group_participants_group_id_foreign FOREIGN KEY (group_id) REFERENCES communications.groups(id) ON DELETE CASCADE;


--
-- Name: commission_records commission_records_commission_config_id_foreign; Type: FK CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.commission_records
    ADD CONSTRAINT commission_records_commission_config_id_foreign FOREIGN KEY (commission_config_id) REFERENCES finance.commission_configs(id);


--
-- Name: commission_records commission_records_payment_id_foreign; Type: FK CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.commission_records
    ADD CONSTRAINT commission_records_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES finance.payments(id);


--
-- Name: credit_transactions credit_transactions_account_id_foreign; Type: FK CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.credit_transactions
    ADD CONSTRAINT credit_transactions_account_id_foreign FOREIGN KEY (account_id) REFERENCES finance.credit_accounts(id) ON DELETE CASCADE;


--
-- Name: invoice_line_items invoice_line_items_invoice_id_foreign; Type: FK CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.invoice_line_items
    ADD CONSTRAINT invoice_line_items_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES finance.invoices(id) ON DELETE CASCADE;


--
-- Name: org_subscriptions org_subscriptions_plan_id_foreign; Type: FK CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.org_subscriptions
    ADD CONSTRAINT org_subscriptions_plan_id_foreign FOREIGN KEY (plan_id) REFERENCES finance.subscription_plans(id);


--
-- Name: payments payments_invoice_id_foreign; Type: FK CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.payments
    ADD CONSTRAINT payments_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES finance.invoices(id) ON DELETE SET NULL;


--
-- Name: promotion_usages promotion_usages_promotion_id_foreign; Type: FK CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.promotion_usages
    ADD CONSTRAINT promotion_usages_promotion_id_foreign FOREIGN KEY (promotion_id) REFERENCES finance.promotions(id) ON DELETE CASCADE;


--
-- Name: subscription_plan_limits subscription_plan_limits_plan_id_foreign; Type: FK CONSTRAINT; Schema: finance; Owner: postgres
--

ALTER TABLE ONLY finance.subscription_plan_limits
    ADD CONSTRAINT subscription_plan_limits_plan_id_foreign FOREIGN KEY (plan_id) REFERENCES finance.subscription_plans(id) ON DELETE CASCADE;


--
-- Name: inventory_batches inventory_batches_warehouse_id_foreign; Type: FK CONSTRAINT; Schema: inventory; Owner: postgres
--

ALTER TABLE ONLY inventory.inventory_batches
    ADD CONSTRAINT inventory_batches_warehouse_id_foreign FOREIGN KEY (warehouse_id) REFERENCES inventory.warehouses(id) ON DELETE RESTRICT;


--
-- Name: stock_alerts stock_alerts_batch_id_foreign; Type: FK CONSTRAINT; Schema: inventory; Owner: postgres
--

ALTER TABLE ONLY inventory.stock_alerts
    ADD CONSTRAINT stock_alerts_batch_id_foreign FOREIGN KEY (batch_id) REFERENCES inventory.inventory_batches(id) ON DELETE CASCADE;


--
-- Name: stock_alerts stock_alerts_warehouse_id_foreign; Type: FK CONSTRAINT; Schema: inventory; Owner: postgres
--

ALTER TABLE ONLY inventory.stock_alerts
    ADD CONSTRAINT stock_alerts_warehouse_id_foreign FOREIGN KEY (warehouse_id) REFERENCES inventory.warehouses(id) ON DELETE SET NULL;


--
-- Name: stock_movements stock_movements_batch_id_foreign; Type: FK CONSTRAINT; Schema: inventory; Owner: postgres
--

ALTER TABLE ONLY inventory.stock_movements
    ADD CONSTRAINT stock_movements_batch_id_foreign FOREIGN KEY (batch_id) REFERENCES inventory.inventory_batches(id) ON DELETE RESTRICT;


--
-- Name: stock_movements stock_movements_warehouse_id_foreign; Type: FK CONSTRAINT; Schema: inventory; Owner: postgres
--

ALTER TABLE ONLY inventory.stock_movements
    ADD CONSTRAINT stock_movements_warehouse_id_foreign FOREIGN KEY (warehouse_id) REFERENCES inventory.warehouses(id);


--
-- Name: stock_reservations stock_reservations_batch_id_foreign; Type: FK CONSTRAINT; Schema: inventory; Owner: postgres
--

ALTER TABLE ONLY inventory.stock_reservations
    ADD CONSTRAINT stock_reservations_batch_id_foreign FOREIGN KEY (batch_id) REFERENCES inventory.inventory_batches(id) ON DELETE CASCADE;


--
-- Name: lg_courier_shipments lg_courier_shipments_courier_account_id_foreign; Type: FK CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_courier_shipments
    ADD CONSTRAINT lg_courier_shipments_courier_account_id_foreign FOREIGN KEY (courier_account_id) REFERENCES logistics.lg_courier_accounts(id) ON DELETE RESTRICT;


--
-- Name: lg_delivery_proofs lg_delivery_proofs_stop_id_foreign; Type: FK CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_delivery_proofs
    ADD CONSTRAINT lg_delivery_proofs_stop_id_foreign FOREIGN KEY (stop_id) REFERENCES logistics.lg_delivery_stops(id) ON DELETE CASCADE;


--
-- Name: lg_delivery_runs lg_delivery_runs_driver_id_foreign; Type: FK CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_delivery_runs
    ADD CONSTRAINT lg_delivery_runs_driver_id_foreign FOREIGN KEY (driver_id) REFERENCES logistics.lg_drivers(id) ON DELETE SET NULL;


--
-- Name: lg_delivery_runs lg_delivery_runs_vehicle_id_foreign; Type: FK CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_delivery_runs
    ADD CONSTRAINT lg_delivery_runs_vehicle_id_foreign FOREIGN KEY (vehicle_id) REFERENCES logistics.lg_vehicles(id) ON DELETE SET NULL;


--
-- Name: lg_delivery_stops lg_delivery_stops_run_id_foreign; Type: FK CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_delivery_stops
    ADD CONSTRAINT lg_delivery_stops_run_id_foreign FOREIGN KEY (run_id) REFERENCES logistics.lg_delivery_runs(id) ON DELETE RESTRICT;


--
-- Name: lg_stop_status_logs lg_stop_status_logs_stop_id_foreign; Type: FK CONSTRAINT; Schema: logistics; Owner: postgres
--

ALTER TABLE ONLY logistics.lg_stop_status_logs
    ADD CONSTRAINT lg_stop_status_logs_stop_id_foreign FOREIGN KEY (stop_id) REFERENCES logistics.lg_delivery_stops(id) ON DELETE CASCADE;


--
-- Name: workflow_runs workflow_runs_workflow_definition_id_foreign; Type: FK CONSTRAINT; Schema: notifications; Owner: postgres
--

ALTER TABLE ONLY notifications.workflow_runs
    ADD CONSTRAINT workflow_runs_workflow_definition_id_foreign FOREIGN KEY (workflow_definition_id) REFERENCES notifications.workflow_definitions(id) ON DELETE RESTRICT;


--
-- Name: workflow_step_logs workflow_step_logs_run_id_foreign; Type: FK CONSTRAINT; Schema: notifications; Owner: postgres
--

ALTER TABLE ONLY notifications.workflow_step_logs
    ADD CONSTRAINT workflow_step_logs_run_id_foreign FOREIGN KEY (run_id) REFERENCES notifications.workflow_runs(id) ON DELETE CASCADE;


--
-- Name: pm_customer_contacts pm_customer_contacts_customer_id_foreign; Type: FK CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_customer_contacts
    ADD CONSTRAINT pm_customer_contacts_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES pharma_marketing.pm_customers(id) ON DELETE CASCADE;


--
-- Name: pm_field_visits pm_field_visits_customer_id_foreign; Type: FK CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_field_visits
    ADD CONSTRAINT pm_field_visits_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES pharma_marketing.pm_customers(id) ON DELETE RESTRICT;


--
-- Name: pm_product_update_deliveries pm_product_update_deliveries_customer_id_foreign; Type: FK CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_product_update_deliveries
    ADD CONSTRAINT pm_product_update_deliveries_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES pharma_marketing.pm_customers(id) ON DELETE CASCADE;


--
-- Name: pm_product_update_deliveries pm_product_update_deliveries_product_update_id_foreign; Type: FK CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_product_update_deliveries
    ADD CONSTRAINT pm_product_update_deliveries_product_update_id_foreign FOREIGN KEY (product_update_id) REFERENCES pharma_marketing.pm_product_updates(id) ON DELETE CASCADE;


--
-- Name: pm_visit_attachments pm_visit_attachments_visit_id_foreign; Type: FK CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_visit_attachments
    ADD CONSTRAINT pm_visit_attachments_visit_id_foreign FOREIGN KEY (visit_id) REFERENCES pharma_marketing.pm_field_visits(id) ON DELETE CASCADE;


--
-- Name: pm_visit_products pm_visit_products_visit_id_foreign; Type: FK CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_visit_products
    ADD CONSTRAINT pm_visit_products_visit_id_foreign FOREIGN KEY (visit_id) REFERENCES pharma_marketing.pm_field_visits(id) ON DELETE CASCADE;


--
-- Name: pm_weekly_plan_items pm_weekly_plan_items_plan_id_foreign; Type: FK CONSTRAINT; Schema: pharma_marketing; Owner: postgres
--

ALTER TABLE ONLY pharma_marketing.pm_weekly_plan_items
    ADD CONSTRAINT pm_weekly_plan_items_plan_id_foreign FOREIGN KEY (plan_id) REFERENCES pharma_marketing.pm_weekly_plans(id) ON DELETE CASCADE;


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

\unrestrict RHAA1eLFhrrvxW6mxs71TldCYyUydNaAERwyYB72MeigoMC7qFlUeqOH9CNMQhS

