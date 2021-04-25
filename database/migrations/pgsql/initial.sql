-- public.api_logs definition

-- Drop table

-- DROP TABLE public.api_logs;

CREATE TABLE public.api_logs (
	id bigserial NOT NULL,
	authorized bool NOT NULL,
	error text NULL,
	key varchar(16) NULL,
	method varchar(6) NOT NULL,
	route text NOT NULL,
	content text NULL,
	user_agent text NOT NULL,
	request_ip varchar(45) NOT NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16416_primary PRIMARY KEY (id)
);


-- public.egg_mount definition

-- Drop table

-- DROP TABLE public.egg_mount;

CREATE TABLE public.egg_mount (
	egg_id int8 NOT NULL,
	mount_id int8 NOT NULL
);
CREATE UNIQUE INDEX idx_16476_egg_mount_egg_id_mount_id_unique ON public.egg_mount USING btree (egg_id, mount_id);


-- public.failed_jobs definition

-- Drop table

-- DROP TABLE public.failed_jobs;

CREATE TABLE public.failed_jobs (
	id bigserial NOT NULL,
	connection text NOT NULL,
	queue text NOT NULL,
	payload text NOT NULL,
	failed_at timestamptz NULL,
	exception text NOT NULL,
	CONSTRAINT idx_16490_primary PRIMARY KEY (id)
);

-- public.jobs definition

-- Drop table

-- DROP TABLE public.jobs;

CREATE TABLE public.jobs (
	id bigserial NOT NULL,
	queue varchar(191) NOT NULL,
	payload text NOT NULL,
	attempts int2 NOT NULL,
	reserved_at int8 NULL,
	available_at int8 NOT NULL,
	created_at int8 NOT NULL,
	CONSTRAINT idx_16499_primary PRIMARY KEY (id)
);
CREATE INDEX idx_16499_jobs_queue_reserved_at_index ON public.jobs USING btree (queue, reserved_at);


-- public.locations definition

-- Drop table

-- DROP TABLE public.locations;

CREATE TABLE public.locations (
	id bigserial NOT NULL,
	short varchar(191) NOT NULL,
	long text NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16508_primary PRIMARY KEY (id)
);
CREATE UNIQUE INDEX idx_16508_locations_short_unique ON public.locations USING btree (short);


-- public.mount_node definition

-- Drop table

-- DROP TABLE public.mount_node;

CREATE TABLE public.mount_node (
	node_id int8 NOT NULL,
	mount_id int8 NOT NULL
);
CREATE UNIQUE INDEX idx_16530_mount_node_node_id_mount_id_unique ON public.mount_node USING btree (node_id, mount_id);


-- public.mount_server definition

-- Drop table

-- DROP TABLE public.mount_server;

CREATE TABLE public.mount_server (
	server_id int8 NOT NULL,
	mount_id int8 NOT NULL
);
CREATE UNIQUE INDEX idx_16533_mount_server_server_id_mount_id_unique ON public.mount_server USING btree (server_id, mount_id);


-- public.mounts definition

-- Drop table

-- DROP TABLE public.mounts;

CREATE TABLE public.mounts (
	id bigserial NOT NULL,
	uuid varchar(36) NOT NULL,
    name varchar(191) NOT NULL,
	description text NULL,
	source varchar(191) NOT NULL,
	target varchar(191) NOT NULL,
	read_only int2 NOT NULL,
	user_mountable int2 NOT NULL,
	CONSTRAINT idx_16523_primary PRIMARY KEY (id)
);
CREATE UNIQUE INDEX idx_16523_mounts_id_unique ON public.mounts USING btree (id);
CREATE UNIQUE INDEX idx_16523_mounts_name_unique ON public.mounts USING btree (name);
CREATE UNIQUE INDEX idx_16523_mounts_uuid_unique ON public.mounts USING btree (uuid);


-- public.nests definition

-- Drop table

-- DROP TABLE public.nests;

CREATE TABLE public.nests (
	id bigserial NOT NULL,
	uuid varchar(36) NOT NULL,
	author varchar(191) NOT NULL,
	name varchar(191) NOT NULL,
	description text NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16538_primary PRIMARY KEY (id)
);
CREATE UNIQUE INDEX idx_16538_services_uuid_unique ON public.nests USING btree (uuid);


-- public.notifications definition

-- Drop table

-- DROP TABLE public.notifications;

CREATE TABLE public.notifications (
	id varchar(191) NOT NULL,
	type varchar(191) NOT NULL,
	notifiable_type varchar(191) NOT NULL,
	notifiable_id numeric NOT NULL,
	data text NOT NULL,
	read_at timestamptz NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16563_primary PRIMARY KEY (id)
);
CREATE INDEX idx_16563_notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


-- public.password_resets definition

-- Drop table

-- DROP TABLE public.password_resets;

CREATE TABLE public.password_resets (
	email varchar(191) NOT NULL,
	token varchar(191) NOT NULL,
	created_at timestamptz NULL
);
CREATE INDEX idx_16569_password_resets_email_index ON public.password_resets USING btree (email);
CREATE INDEX idx_16569_password_resets_token_index ON public.password_resets USING btree (token);

-- public.sessions definition

-- Drop table

-- DROP TABLE public.sessions;

CREATE TABLE public.sessions (
	id varchar(191) NOT NULL,
	user_id int8 NULL,
	ip_address varchar(45) NULL,
	user_agent text NULL,
	payload text NOT NULL,
	last_activity int8 NOT NULL
);
CREATE UNIQUE INDEX idx_16619_sessions_id_unique ON public.sessions USING btree (id);


-- public.settings definition

-- Drop table

-- DROP TABLE public.settings;

CREATE TABLE public.settings (
	id bigserial NOT NULL,
	key varchar(191) NOT NULL,
	value text NOT NULL,
	CONSTRAINT idx_16627_primary PRIMARY KEY (id)
);
CREATE UNIQUE INDEX idx_16627_settings_key_unique ON public.settings USING btree (key);


-- public.tasks_log definition

-- Drop table

-- DROP TABLE public.tasks_log;

CREATE TABLE public.tasks_log (
	id bigserial NOT NULL,
	task_id int8 NOT NULL,
	run_time timestamptz NULL,
	run_status int8 NOT NULL,
	response text NOT NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16654_primary PRIMARY KEY (id)
);

-- public.users definition

-- Drop table

-- DROP TABLE public.users;

CREATE TABLE public.users (
	id bigserial NOT NULL,
	external_id varchar(191) NULL,
	uuid varchar(36) NOT NULL,
	username varchar(191) NOT NULL,
	email varchar(191) NOT NULL,
	name_first varchar(191) NULL,
	name_last varchar(191) NULL,
	password text NOT NULL,
	remember_token varchar(191) NULL,
	language varchar(5) NOT NULL DEFAULT 'en'::bpchar,
	root_admin int2 NOT NULL DEFAULT '0'::smallint,
	use_totp int2 NOT NULL,
	totp_secret text NULL,
	totp_authenticated_at timestamptz NULL,
	gravatar bool NOT NULL DEFAULT true,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16663_primary PRIMARY KEY (id)
);
CREATE UNIQUE INDEX idx_16663_users_email_unique ON public.users USING btree (email);
CREATE INDEX idx_16663_users_external_id_index ON public.users USING btree (external_id);
CREATE UNIQUE INDEX idx_16663_users_username_unique ON public.users USING btree (username);
CREATE UNIQUE INDEX idx_16663_users_uuid_unique ON public.users USING btree (uuid);


-- public.api_keys definition

-- Drop table

-- DROP TABLE public.api_keys;

CREATE TABLE public.api_keys (
	id bigserial NOT NULL,
	user_id int8 NOT NULL,
	key_type int2 NOT NULL DEFAULT '0'::smallint,
	identifier varchar(16) NULL,
	token text NOT NULL,
	allowed_ips text NULL,
	memo text NULL,
	last_used_at timestamptz NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	r_servers int2 NOT NULL DEFAULT '0'::smallint,
	r_nodes int2 NOT NULL DEFAULT '0'::smallint,
	r_allocations int2 NOT NULL DEFAULT '0'::smallint,
	r_users int2 NOT NULL DEFAULT '0'::smallint,
	r_locations int2 NOT NULL DEFAULT '0'::smallint,
	r_nests int2 NOT NULL DEFAULT '0'::smallint,
	r_eggs int2 NOT NULL DEFAULT '0'::smallint,
	r_database_hosts int2 NOT NULL DEFAULT '0'::smallint,
	r_server_databases int2 NOT NULL DEFAULT '0'::smallint,
	CONSTRAINT idx_16397_primary PRIMARY KEY (id),
	CONSTRAINT api_keys_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE CASCADE
);
CREATE UNIQUE INDEX idx_16397_api_keys_identifier_unique ON public.api_keys USING btree (identifier);
CREATE INDEX idx_16397_api_keys_user_id_foreign ON public.api_keys USING btree (user_id);


-- public.eggs definition

-- Drop table

-- DROP TABLE public.eggs;

CREATE TABLE public.eggs (
	id bigserial NOT NULL,
	uuid varchar(36) NOT NULL,
	nest_id int8 NOT NULL,
	author varchar(191) NOT NULL,
	name varchar(191) NOT NULL,
	description text NULL,
	features json NULL,
	docker_images json NULL,
	file_denylist json NULL,
	update_url text NULL,
	config_files text NULL,
	config_startup text NULL,
	config_logs text NULL,
	config_stop varchar(191) NULL,
	config_from int8 NULL,
	startup text NULL,
	script_container varchar(191) NOT NULL DEFAULT 'alpine:3.4'::character varying,
	copy_script_from int8 NULL,
	script_entry varchar(191) NOT NULL DEFAULT 'ash'::character varying,
	script_is_privileged bool NOT NULL DEFAULT true,
	script_install text NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16466_primary PRIMARY KEY (id),
	CONSTRAINT eggs_config_from_foreign FOREIGN KEY (config_from) REFERENCES eggs(id) ON UPDATE RESTRICT ON DELETE SET NULL,
	CONSTRAINT eggs_copy_script_from_foreign FOREIGN KEY (copy_script_from) REFERENCES eggs(id) ON UPDATE RESTRICT ON DELETE SET NULL,
	CONSTRAINT service_options_nest_id_foreign FOREIGN KEY (nest_id) REFERENCES nests(id) ON UPDATE RESTRICT ON DELETE CASCADE
);
CREATE INDEX idx_16466_eggs_config_from_foreign ON public.eggs USING btree (config_from);
CREATE INDEX idx_16466_eggs_copy_script_from_foreign ON public.eggs USING btree (copy_script_from);
CREATE INDEX idx_16466_service_options_nest_id_foreign ON public.eggs USING btree (nest_id);
CREATE UNIQUE INDEX idx_16466_service_options_uuid_unique ON public.eggs USING btree (uuid);


-- public.nodes definition

-- Drop table

-- DROP TABLE public.nodes;

CREATE TABLE public.nodes (
	id bigserial NOT NULL,
	uuid varchar(36) NOT NULL,
	public int4 NOT NULL,
	name varchar(191) NOT NULL,
	description text NULL,
	location_id int8 NOT NULL,
	fqdn varchar(191) NOT NULL,
	scheme varchar(191) NOT NULL DEFAULT 'https'::character varying,
	behind_proxy bool NOT NULL DEFAULT false,
	maintenance_mode bool NOT NULL DEFAULT false,
	memory int8 NOT NULL,
	memory_overallocate int8 NOT NULL DEFAULT '0'::bigint,
	disk int8 NOT NULL,
	disk_overallocate int8 NOT NULL DEFAULT '0'::bigint,
	upload_size int8 NOT NULL DEFAULT '100'::bigint,
	daemon_token_id varchar(16) NOT NULL,
	daemon_token text NOT NULL,
	"daemonListen" int4 NOT NULL DEFAULT 8080,
	"daemonSFTP" int4 NOT NULL DEFAULT 2022,
	"daemonBase" varchar(191) NOT NULL DEFAULT '/home/daemon-files'::character varying,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16547_primary PRIMARY KEY (id),
	CONSTRAINT nodes_location_id_foreign FOREIGN KEY (location_id) REFERENCES locations(id) ON UPDATE RESTRICT ON DELETE RESTRICT
);
CREATE UNIQUE INDEX idx_16547_nodes_daemon_token_id_unique ON public.nodes USING btree (daemon_token_id);
CREATE INDEX idx_16547_nodes_location_id_foreign ON public.nodes USING btree (location_id);
CREATE UNIQUE INDEX idx_16547_nodes_uuid_unique ON public.nodes USING btree (uuid);


-- public.recovery_tokens definition

-- Drop table

-- DROP TABLE public.recovery_tokens;

CREATE TABLE public.recovery_tokens (
	id bigserial NOT NULL,
	user_id int8 NOT NULL,
	token varchar(191) NOT NULL,
	created_at timestamptz NULL,
	CONSTRAINT idx_16574_primary PRIMARY KEY (id),
	CONSTRAINT recovery_tokens_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE CASCADE
);
CREATE INDEX idx_16574_recovery_tokens_user_id_foreign ON public.recovery_tokens USING btree (user_id);


-- public.database_hosts definition

-- Drop table

-- DROP TABLE public.database_hosts;

CREATE TABLE public.database_hosts (
	id bigserial NOT NULL,
	name varchar(191) NOT NULL,
	host varchar(191) NOT NULL,
	port int8 NOT NULL,
	username varchar(191) NOT NULL,
	password text NOT NULL,
	max_databases int8 NULL,
	node_id int8 NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16457_primary PRIMARY KEY (id),
	CONSTRAINT database_hosts_node_id_foreign FOREIGN KEY (node_id) REFERENCES nodes(id) ON UPDATE RESTRICT ON DELETE SET NULL
);
CREATE INDEX idx_16457_database_hosts_node_id_foreign ON public.database_hosts USING btree (node_id);


-- public.egg_variables definition

-- Drop table

-- DROP TABLE public.egg_variables;

CREATE TABLE public.egg_variables (
	id bigserial NOT NULL,
	egg_id int8 NOT NULL,
	name varchar(191) NOT NULL,
	description text NOT NULL,
	env_variable varchar(191) NOT NULL,
	default_value text NOT NULL,
	user_viewable int2 NOT NULL,
	user_editable int2 NOT NULL,
	rules text NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16481_primary PRIMARY KEY (id),
	CONSTRAINT service_variables_egg_id_foreign FOREIGN KEY (egg_id) REFERENCES eggs(id) ON UPDATE RESTRICT ON DELETE CASCADE
);
CREATE INDEX idx_16481_service_variables_egg_id_foreign ON public.egg_variables USING btree (egg_id);


-- public.allocations definition

-- Drop table

-- DROP TABLE public.allocations;

CREATE TABLE public.allocations (
	id bigserial NOT NULL,
	node_id int8 NOT NULL,
	ip varchar(191) NOT NULL,
	ip_alias text NULL,
	port int4 NOT NULL,
	server_id int8 NULL,
	notes varchar(191) NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16388_primary PRIMARY KEY (id)
);
CREATE UNIQUE INDEX idx_16388_allocations_node_id_ip_port_unique ON public.allocations USING btree (node_id, ip, port);
CREATE INDEX idx_16388_allocations_server_id_foreign ON public.allocations USING btree (server_id);


-- public.audit_logs definition

-- Drop table

-- DROP TABLE public.audit_logs;

CREATE TABLE public.audit_logs (
	id bigserial NOT NULL,
	uuid varchar(36) NOT NULL,
	is_system bool NOT NULL DEFAULT false,
	user_id int8 NULL,
	server_id int8 NULL,
	action varchar(191) NOT NULL,
	subaction varchar(191) NULL,
	device json NOT NULL,
	metadata json NOT NULL,
	created_at timestamptz NULL,
	CONSTRAINT idx_16425_primary PRIMARY KEY (id)
);
CREATE INDEX idx_16425_audit_logs_action_server_id_index ON public.audit_logs USING btree (action, server_id);
CREATE INDEX idx_16425_audit_logs_server_id_foreign ON public.audit_logs USING btree (server_id);
CREATE INDEX idx_16425_audit_logs_user_id_foreign ON public.audit_logs USING btree (user_id);

-- public.backups definition

-- Drop table

-- DROP TABLE public.backups;

CREATE TABLE public.backups (
	id bigserial NOT NULL,
	server_id int8 NOT NULL,
	uuid varchar(36) NOT NULL,
	upload_id text NULL,
	is_successful bool NOT NULL DEFAULT true,
	name varchar(191) NOT NULL,
	ignored_files text NOT NULL,
	disk varchar(191) NOT NULL,
	checksum varchar(191) NULL,
	bytes numeric NOT NULL DEFAULT '0'::numeric,
	completed_at timestamptz NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	deleted_at timestamptz NULL,
	CONSTRAINT idx_16435_primary PRIMARY KEY (id)
);
CREATE INDEX idx_16435_backups_server_id_foreign ON public.backups USING btree (server_id);
CREATE UNIQUE INDEX idx_16435_backups_uuid_unique ON public.backups USING btree (uuid);


-- public.databases definition

-- Drop table

-- DROP TABLE public.databases;

CREATE TABLE public.databases (
	id bigserial NOT NULL,
	server_id int8 NOT NULL,
	database_host_id int8 NOT NULL,
	database varchar(191) NOT NULL,
	username varchar(191) NOT NULL,
	remote varchar(191) NOT NULL DEFAULT '%'::character varying,
	password text NOT NULL,
	max_connections int8 NULL DEFAULT '0'::bigint,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16446_primary PRIMARY KEY (id)
);
CREATE UNIQUE INDEX idx_16446_databases_database_host_id_server_id_database_unique ON public.databases USING btree (database_host_id, server_id, database);
CREATE UNIQUE INDEX idx_16446_databases_database_host_id_username_unique ON public.databases USING btree (database_host_id, username);
CREATE INDEX idx_16446_databases_server_id_foreign ON public.databases USING btree (server_id);


-- public.schedules definition

-- Drop table

-- DROP TABLE public.schedules;

CREATE TABLE public.schedules (
	id bigserial NOT NULL,
	server_id int8 NOT NULL,
	name varchar(191) NOT NULL,
	cron_day_of_week varchar(191) NOT NULL,
	cron_month varchar(191) NOT NULL,
	cron_day_of_month varchar(191) NOT NULL,
	cron_hour varchar(191) NOT NULL,
	cron_minute varchar(191) NOT NULL,
	is_active bool NOT NULL,
	is_processing bool NOT NULL,
	last_run_at timestamptz NULL,
	next_run_at timestamptz NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16580_primary PRIMARY KEY (id)
);
CREATE INDEX idx_16580_schedules_server_id_foreign ON public.schedules USING btree (server_id);


-- public.server_transfers definition

-- Drop table

-- DROP TABLE public.server_transfers;

CREATE TABLE public.server_transfers (
	id bigserial NOT NULL,
	server_id int8 NOT NULL,
	successful bool NULL,
	old_node int8 NOT NULL,
	new_node int8 NOT NULL,
	old_allocation int8 NOT NULL,
	new_allocation int8 NOT NULL,
	old_additional_allocations json NULL,
	new_additional_allocations json NULL,
	archived bool NOT NULL DEFAULT false,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16602_primary PRIMARY KEY (id)
);
CREATE INDEX idx_16602_server_transfers_server_id_foreign ON public.server_transfers USING btree (server_id);


-- public.server_variables definition

-- Drop table

-- DROP TABLE public.server_variables;

CREATE TABLE public.server_variables (
	id bigserial NOT NULL,
	server_id int8 NULL,
	variable_id int8 NOT NULL,
	variable_value text NOT NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16612_primary PRIMARY KEY (id)
);
CREATE INDEX idx_16612_server_variables_server_id_foreign ON public.server_variables USING btree (server_id);
CREATE INDEX idx_16612_server_variables_variable_id_foreign ON public.server_variables USING btree (variable_id);


-- public.servers definition

-- Drop table

-- DROP TABLE public.servers;

CREATE TABLE public.servers (
	id bigserial NOT NULL,
	external_id varchar(191) NULL,
	uuid varchar(36) NOT NULL,
	"uuidShort" varchar(8) NOT NULL,
	node_id int8 NOT NULL,
	name varchar(191) NOT NULL,
	description text NOT NULL,
	status varchar(191) NULL,
	skip_scripts bool NOT NULL DEFAULT false,
	owner_id int8 NOT NULL,
	memory int8 NOT NULL,
	swap int8 NOT NULL,
	disk int8 NOT NULL,
	io int8 NOT NULL,
	cpu int8 NOT NULL,
	threads varchar(191) NULL,
	oom_disabled int2 NOT NULL DEFAULT '0'::smallint,
	allocation_id int8 NOT NULL,
	nest_id int8 NOT NULL,
	egg_id int8 NOT NULL,
	startup text NOT NULL,
	image varchar(191) NOT NULL,
	allocation_limit int8 NULL,
	database_limit int8 NULL DEFAULT '0'::bigint,
	backup_limit int8 NOT NULL DEFAULT '0'::bigint,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16589_primary PRIMARY KEY (id)
);
CREATE UNIQUE INDEX idx_16589_servers_allocation_id_unique ON public.servers USING btree (allocation_id);
CREATE INDEX idx_16589_servers_egg_id_foreign ON public.servers USING btree (egg_id);
CREATE UNIQUE INDEX idx_16589_servers_external_id_unique ON public.servers USING btree (external_id);
CREATE INDEX idx_16589_servers_nest_id_foreign ON public.servers USING btree (nest_id);
CREATE INDEX idx_16589_servers_node_id_foreign ON public.servers USING btree (node_id);
CREATE INDEX idx_16589_servers_owner_id_foreign ON public.servers USING btree (owner_id);
CREATE UNIQUE INDEX idx_16589_servers_uuid_unique ON public.servers USING btree (uuid);
CREATE UNIQUE INDEX idx_16589_servers_uuidshort_unique ON public.servers USING btree ("uuidShort");


-- public.subusers definition

-- Drop table

-- DROP TABLE public.subusers;

CREATE TABLE public.subusers (
	id bigserial NOT NULL,
	user_id int8 NOT NULL,
	server_id int8 NOT NULL,
	permissions json NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16636_primary PRIMARY KEY (id)
);
CREATE INDEX idx_16636_subusers_server_id_foreign ON public.subusers USING btree (server_id);
CREATE INDEX idx_16636_subusers_user_id_foreign ON public.subusers USING btree (user_id);


-- public.tasks definition

-- Drop table

-- DROP TABLE public.tasks;

CREATE TABLE public.tasks (
	id bigserial NOT NULL,
	schedule_id int8 NOT NULL,
	sequence_id int8 NOT NULL,
	action varchar(191) NOT NULL,
	payload text NOT NULL,
	time_offset int8 NOT NULL,
	is_queued bool NOT NULL,
	created_at timestamptz NULL,
	updated_at timestamptz NULL,
	CONSTRAINT idx_16645_primary PRIMARY KEY (id)
);
CREATE INDEX idx_16645_tasks_schedule_id_sequence_id_index ON public.tasks USING btree (schedule_id, sequence_id);


-- public.allocations foreign keys

ALTER TABLE public.allocations ADD CONSTRAINT allocations_node_id_foreign FOREIGN KEY (node_id) REFERENCES public.nodes(id) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE public.allocations ADD CONSTRAINT allocations_server_id_foreign FOREIGN KEY (server_id) REFERENCES public.servers(id) ON DELETE SET NULL ON UPDATE RESTRICT;


-- public.audit_logs foreign keys

ALTER TABLE public.audit_logs ADD CONSTRAINT audit_logs_server_id_foreign FOREIGN KEY (server_id) REFERENCES public.servers(id) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE public.audit_logs ADD CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL ON UPDATE RESTRICT;


-- public.backups foreign keys

ALTER TABLE public.backups ADD CONSTRAINT backups_server_id_foreign FOREIGN KEY (server_id) REFERENCES public.servers(id) ON DELETE CASCADE ON UPDATE RESTRICT;


-- public.databases foreign keys

ALTER TABLE public.databases ADD CONSTRAINT databases_database_host_id_foreign FOREIGN KEY (database_host_id) REFERENCES public.database_hosts(id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE public.databases ADD CONSTRAINT databases_server_id_foreign FOREIGN KEY (server_id) REFERENCES public.servers(id) ON DELETE RESTRICT ON UPDATE RESTRICT;


-- public.schedules foreign keys

ALTER TABLE public.schedules ADD CONSTRAINT schedules_server_id_foreign FOREIGN KEY (server_id) REFERENCES public.servers(id) ON DELETE CASCADE ON UPDATE RESTRICT;


-- public.server_transfers foreign keys

ALTER TABLE public.server_transfers ADD CONSTRAINT server_transfers_server_id_foreign FOREIGN KEY (server_id) REFERENCES public.servers(id) ON DELETE CASCADE ON UPDATE RESTRICT;


-- public.server_variables foreign keys

ALTER TABLE public.server_variables ADD CONSTRAINT server_variables_server_id_foreign FOREIGN KEY (server_id) REFERENCES public.servers(id) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE public.server_variables ADD CONSTRAINT server_variables_variable_id_foreign FOREIGN KEY (variable_id) REFERENCES public.egg_variables(id) ON DELETE CASCADE ON UPDATE RESTRICT;


-- public.servers foreign keys

ALTER TABLE public.servers ADD CONSTRAINT servers_allocation_id_foreign FOREIGN KEY (allocation_id) REFERENCES public.allocations(id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE public.servers ADD CONSTRAINT servers_egg_id_foreign FOREIGN KEY (egg_id) REFERENCES public.eggs(id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE public.servers ADD CONSTRAINT servers_nest_id_foreign FOREIGN KEY (nest_id) REFERENCES public.nests(id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE public.servers ADD CONSTRAINT servers_node_id_foreign FOREIGN KEY (node_id) REFERENCES public.nodes(id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE public.servers ADD CONSTRAINT servers_owner_id_foreign FOREIGN KEY (owner_id) REFERENCES public.users(id) ON DELETE RESTRICT ON UPDATE RESTRICT;


-- public.subusers foreign keys

ALTER TABLE public.subusers ADD CONSTRAINT subusers_server_id_foreign FOREIGN KEY (server_id) REFERENCES public.servers(id) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE public.subusers ADD CONSTRAINT subusers_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE ON UPDATE RESTRICT;


-- public.tasks foreign keys

ALTER TABLE public.tasks ADD CONSTRAINT tasks_schedule_id_foreign FOREIGN KEY (schedule_id) REFERENCES public.schedules(id) ON DELETE CASCADE ON UPDATE RESTRICT;