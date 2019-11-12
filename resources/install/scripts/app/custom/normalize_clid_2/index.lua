-- Set effective_caller_id name and number regarding different rules, lise outbound and also..

opthelp = [[
 -n, --no-ani                       Do not prepend ANI
 -p, --prepend-plus                 Make sure callerid name/number have plus on the start
 -d, --direct-connect               If it's set - use Direct connection to trunk (Telgo Mode)
]]

opts, args, err = require('app.custom.functions.optargs').from_opthelp(opthelp, argv)

if opts == nil then
    freeswitch.consoleLog("ERROR", "[silence_detect] Options are not parsable " .. err)
    do return end
end


if ( session:ready() ) then
        
    ani = session:getVariable("ani")
    ani = ani:gsub("%D", "")

    -- Check if call is forwarded
    if (ani:len() > 5) then -- Seems, we have forwaded call
        
        ani = (opts.p) and ("+" .. ani) or (ani)              -- Prepend (or not) "+"" to callerID
        callerid_name = (opts.d) and (ani) or ("F" .. ani)    -- Use to mark Forwarded calls

        freeswitch.consoleLog("notice", "[NORMALIZE CALLERID] Call is forwarded. Setting callerid to " .. callerid_name .. "(" .. ani .. ")\n")
        
        session:setVariable("effective_caller_id_name", callerid_name)
        session:setVariable("effective_caller_id_number", ani)
        do return end
    end

    outbound_caller_id_name = session:getVariable("outbound_caller_id_name") or ""
    company_caller_id = session:getVariable("company_caller_id") or ""

    -- Check for anonymous
    if string.find(outbound_caller_id_name:lower(), "anon") then
        freeswitch.consoleLog("notice", "[NORMALIZE CALLERID] Call is anonymous.\n")
        -- No matter what to do, just set callerid to Anon
        if opts.d then -- Direct mode - use Telgo settings
            session:setVariable("effective_caller_id_name", "anonymous")
            session:setVariable("effective_caller_id_number", "anonymous")
            session:setVariable("sip_cid_type","rpid")
            session:setVariable("origination_privacy", "screen+hide_name+hide_number")
            do return end
        end
        session:setVariable("effective_caller_id_name", "anonymous")
        
        company_caller_id = (opts.p) and ("+" .. company_caller_id:gsub("%D", "")) or (company_caller_id)
        company_caller_id = (opts.n) and (company_caller_id) or (company_caller_id .. ani)

        session:setVariable("effective_caller_id_number", company_caller_id)
        do return end
    end

    -- Check for outbound callerid name/number...
    outbound_caller_id_number = session:getVariable("outbound_caller_id_number")

    if outbound_caller_id_number or outbound_caller_id_name:len() > 0 then

        if outbound_caller_id_number then
            outbound_caller_id_name = outbound_caller_id_name:len() > 0 and outbound_caller_id_name or outbound_caller_id_number
        else
            outbound_caller_id_number = outbound_caller_id_name
        end

        outbound_caller_id_number = (opts.p) and ( "+" .. outbound_caller_id_number:gsub("%D", "")) or (outbound_caller_id_number)
        outbound_caller_id_name = (opts.p) and ( "+" .. outbound_caller_id_name:gsub("%D", "")) or (outbound_caller_id_name)

        freeswitch.consoleLog("notice", "[NORMALIZE CALLERID] Callerid is set based on user settings " .. outbound_caller_id_name .. "(" .. outbound_caller_id_number .. ")\n")

        session:setVariable("effective_caller_id_name", outbound_caller_id_name)
        session:setVariable("effective_caller_id_number", outbound_caller_id_number)
        do return end
    end

    -- Normal outbound call

    if company_caller_id:len() > 0 then

        company_caller_id = (opts.p) and ("+" .. company_caller_id:gsub("%D", "")) or (company_caller_id)
        company_caller_id = (opts.n) and (company_caller_id) or (company_caller_id .. ani)
        
        freeswitch.consoleLog("notice", "[NORMALIZE CALLERID] Callerid is set based on company_caller_id: " .. callerid_prefix .. company_caller_id .. "\n")
        session:setVariable("effective_caller_id_name", company_caller_id)
        session:setVariable("effective_caller_id_number", company_caller_id)
        do return end
    end

    freeswitch.consoleLog("notice", "[NORMALIZE CALLERID] No action were preformed.\n")

end