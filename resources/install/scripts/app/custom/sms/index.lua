--	sms.lua
--	Part of FusionPBX
--	Copyright (C) 2010-2017 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	   this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	   notice, this list of conditions and the following disclaimer in the
--	   documentation and/or other materials provided with the distribution.
--
--	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.

opthelp = [[
 -s, --source=OPTARG	Source of the message
 -d, --debug			Debug flag			
]]


function save_sms_to_database(db, params)
	sql = "INSERT INTO v_sms_messages "
	sql = sql .."( "
	sql = sql .."domain_uuid, "
	sql = sql .."sms_message_uuid, "
	sql = sql .."sms_message_timestamp, "
	sql = sql .."sms_message_from, "
	sql = sql .."sms_message_to, "
	sql = sql .."sms_message_direction, "
	sql = sql .."sms_message_text, "
	sql = sql .."sms_message_status "
	sql = sql ..") "
	sql = sql .."VALUES ( "
	sql = sql ..":domain_uuid, "
	sql = sql ..":sms_message_uuid, "
	sql = sql .."now(), "
	sql = sql ..":sms_message_from, "
	sql = sql ..":sms_message_to, "
	sql = sql ..":sms_message_direction, "
	sql = sql ..":sms_message_text, "
	sql = sql ..":sms_message_status "
	sql = sql ..")"

	--run the query
	db:query(sql, params);
end


local log = require "resources.functions.log".sms

local Settings = require "resources.functions.lazy_settings"
local Database = require "resources.functions.database"
require "resources.functions.trim";

api = freeswitch.API();

local db = dbh or Database.new('system')
--exits the script if we didn't connect properly
assert(db:connected());

opts, args, err = require('app.custom.functions.optargs').from_opthelp(opthelp, argv)

if opts == nil then
    log.error("Options are not parsable " .. err)
    do return end
end

local sms_source = opts.s or 'internal'

if sms_source == 'internal' then
	if opts.d then log.info("Message source is internal. Saving to database") end

	uuid               = message:getHeader("Core-UUID")
	from_user          = message:getHeader("from_user")
	from_domain        = message:getHeader("from_host")
	to_user            = message:getHeader("to_user")
	to_domain          = message:getHeader("to_host") or from_domain
	content_type       = message:getHeader("type")
	sms_message_text   = message:getBody()

	--Clean body up for Groundwire send
	local sms_message_text_raw = sms_message_text
	local _, sms_temp_end = string.find(sms_message_text_raw, 'Content%-length:')
	if sms_temp_end == nil then
		sms_message_text = sms_message_text_raw
	else
		_, sms_temp_end = string.find(sms_message_text_raw, '\r\n\r\n', sms_temp_end)
		if sms_temp_end == nil then
			sms_message_text = sms_message_text_raw
		else
			sms_message_text = string.sub(sms_message_text_raw, sms_temp_end + 1)
		end
	end

	sms_message_text = sms_message_text:gsub('%"','')
	sms_type      	 = 'sms'

	-- Getting from/to user data
	local domain_uuid
	if (from_user and from_domain) then
		sms_message_from   = from_user .. '@' .. from_domain
		-- Getting domain_uuid
		cmd = "user_data ".. from_user .. "@" .. from_domain .. " var domain_uuid"
		domain_uuid = trim(api:executeString(cmd))
		-- Getting from_user_exists
		cmd = "user_exists id ".. from_user .." "..from_domain
		from_user_exists = api:executeString(cmd)
	else 
		log.error("From user or from domain is not existed. Cannot prcess this message as internal")
		do return end
	end

	if opts.d then log.notice("From user exists: " .. from_user_exists) end

	if (to_user and to_domain) then
		sms_message_to     = to_user .. '@' .. to_domain

		cmd = "user_exists id ".. to_user .." "..to_domain
		to_user_exists = api:executeString(cmd)
	else
		to_user_exists = 'false'
	end
	-- End getting from/to user data
	
	if (from_user_exists == 'false') then
		log.error("From user is not exists. Cannot process this request")
		do return end
	end

	if not domain_uuid then
		log.error("Please make sure " .. domain_name .. " is existed on the system")
		do return end
	end

	-- Get settings
	local settings = Settings.new(db, from_domain, domain_uuid)

	if (to_user_exists == 'true') then

		--set the parameters for database save
		local params= {
			domain_uuid = domain_uuid,
			sms_message_uuid = api:executeString("create_uuid"),
			sms_message_from = sms_message_from,
			sms_message_to = sms_message_to,
			sms_message_direction = 'send',
			sms_message_status = 'Sent. Local',
			sms_message_text = sms_message_text,
		}

		save_sms_to_database(db, params)

		do return end
	end

	-- SMS to external

	if not to_user then
		local params= {
			domain_uuid = domain_uuid,
			sms_message_uuid = api:executeString("create_uuid"),
			sms_message_from = sms_message_from,
			sms_message_to = "NA",
			sms_message_direction = 'send',
			sms_message_status = 'Error. No TO user specified',
			sms_message_text = sms_message_text,
		}
		save_sms_to_database(db, params)

		log.error('To user is empty. Discarding sent')
		do return end
	end

	-- Get routing rules for this message type.
	sql =        "SELECT sms_routing_source, "
	sql = sql .. "sms_routing_destination, "
	sql = sql .. "sms_routing_target_details"
	sql = sql .. " FROM v_sms_routing WHERE"
	sql = sql .. " domain_uuid = :domain_uuid"
	sql = sql .. " AND sms_routing_target_type = 'carrier'"
	sql = sql .. " AND sms_routing_enabled = 'true'"

	local params = {
		domain_uuid = domain_uuid
	}

	local routing_patterns = {}
	db:query(sql, params, function(row)
		table.insert(routing_patterns, row)
	end);
	
	local sms_carrier

	if (#routing_patterns == 0) then

		local params= {
			domain_uuid = domain_uuid,
			sms_message_uuid = api:executeString("create_uuid"),
			sms_message_from = sms_message_from,
			sms_message_to = to_user,
			sms_message_direction = 'send',
			sms_message_status = 'Error. No routing patterns',
			sms_message_text = sms_message_text,
		}
		save_sms_to_database(db, params)

		log.notice("External routing table is empty. Exiting.")

		do return end
	end

	for _, routing_pattern in pairs(routing_patterns) do
		sms_routing_source = routing_pattern['sms_routing_source']
		sms_routing_destination = routing_pattern['sms_routing_destination']

		if (from_user:find(sms_routing_source) and to_user:find(sms_routing_destination)) then
			sms_carrier = routing_pattern['sms_routing_target_details']
			if opts.d then log.notice("Using " .. sms_carrier .. " for this SMS") end
			break
		end
	end

	if (not sms_carrier) then

		local params= {
			domain_uuid = domain_uuid,
			sms_message_uuid = api:executeString("create_uuid"),
			sms_message_from = sms_message_from,
			sms_message_to = to_user,
			sms_message_direction = 'send',
			sms_message_status = 'Error. No carrier found',
			sms_message_text = sms_message_text,
		}
		save_sms_to_database(db, params)

		log.warning("Cannot find carrier for this SMS: From:" .. sms_message_from .. "  To: " .. sms_message_to)
		do return end
	end

	local sms_request_type = settings:get('sms', sms_carrier .. '_request_type', 'text')
	local sms_carrier_url = settings:get('sms', sms_carrier .. "_url", 'text')
	local sms_carrier_user = settings:get('sms', sms_carrier .. "_user", 'text')
	local sms_carrier_password = settings:get("sms", sms_carrier .. "_password", 'text')
	local sms_carrier_body_type = settings:get("sms", sms_carrier .. "_body", "text")
	local sms_carrier_content_type = settings:get("sms", sms_carrier .. "_content_type", "text") or "application/json"
	local sms_carrier_method =  settings:get("sms", sms_carrier .. "_method", "text") or 'post'

	--get the sip user outbound_caller_id
	cmd = "user_data ".. from_user .."@"..from_host.." var outbound_caller_id_number"
	caller_id_from = trim(api:executeString(cmd)) or from_user

	--replace variables for their value
	if (sms_carrier_url) then
		sms_carrier_url = sms_carrier_user and sms_carrier_url:gsub("${user}", sms_carrier_user) or sms_carrier_url
		sms_carrier_url = sms_carrier_password and sms_carrier_url:gsub("${password}", sms_carrier_password) or sms_carrier_url
		sms_carrier_url = sms_carrier_url:gsub("${from}", caller_id_from)
		sms_carrier_url = sms_carrier_url:gsub("${to}", to_user)
		sms_carrier_url = sms_carrier_url:gsub("${text}", sms_message_text)
	end

	if (sms_carrier_body) then
		sms_carrier_body = sms_carrier_user and sms_carrier_body:gsub("${user}", sms_carrier_user) or sms_carrier_body
		sms_carrier_body = sms_carrier_password and sms_carrier_body:gsub("${password}", sms_carrier_password) or sms_carrier_body
		sms_carrier_body = sms_carrier_body:gsub("${from}", caller_id_from)
		sms_carrier_body = sms_carrier_body:gsub("${to}", to_user)
		sms_carrier_body = sms_carrier_body:gsub("${text}", sms_message_text)
	end

	-- Send to the provider using curl
	cmd = "curl " .. sms_carrier_url .. " content-type " .. sms_carrier_content_type .. " " .. sms_carrier_method .. " " .. sms_carrier_body
	api:executeString(cmd)

	local params= {
		domain_uuid = domain_uuid,
		sms_message_uuid = api:executeString("create_uuid"),
		sms_message_from = sms_message_from,
		sms_message_to = to_user,
		sms_message_direction = 'send',
		sms_message_status = 'Sent. ' .. sms_carrier ,
		sms_message_text = sms_message_text,
	}
	save_sms_to_database(db, params)


else 
	log.warning("[sms] Source " .. sms_source .. " is not yet implemented")
end