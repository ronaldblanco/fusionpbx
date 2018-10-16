require "app.custom.silence_detect.resources.functions.silence_detect_functions"
require "app.custom.silence_detect.resources.functions.wav"

opthelp = [[
 -a, --algo=OPTARG                  Algorythm used. lines or samples
 -s, --temporary-storage=OPTARG     Temporary storage
 -l, --loops=OPTARG                 Loop count
 -r, --ringback=OPTARG              Ringback to be used
 -t, --transfer-on-silence=OPTARG   Where to transfer on silence
 -i, --include-pattern=COUNT        Include callerid_number pattern     
 -e, --exclude-pattern=COUNT        Exclude callerid_number pattern
 -c, --clid-lenght=COUNT            If specified, only these callerid lenght are processed
 -d, --debug                        If specified - debug variables are set
 -v, --verbose                      If specified - verbose info is printed on FS console
]]

opts, args = require('app.custom.functions.optargs').from_opthelp(opthelp, argv)

if session:ready() then


    local check_exit = false

    local callerid_number = session:getVariable('caller_id_number') or ''
    -- Filter callerid on digits
    callerid_number = string.gsub(callerid_number, "%D", '') or ''

    -- Check callerid lenght
    if opts.c then
        check_exit = true
        for _, v in pairs(opts.c) do
            if (tonumber(v) == #callerid_number) then
                check_exit = false
                break
            end
        end
    end

    if (check_exit) then
        freeswitch.consoleLog("NOTICE", "[silence_detect] Callerid length is " .. #callerid_number .. " and not match options")
        do return end
    end

    -- Check for included callerid patterns
    if opts.i then
        check_exit = true
        for _, v in pairs(opts.i) do
            if (string.match(callerid_number, v)) then
                check_exit = false
                break
            end
        end
    end

    if (check_exit) then
        freeswitch.consoleLog("NOTICE", "[silence_detect] Callerid  " .. callerid_number .. " not matched included options")
        do return end
    end    

    if opts.e then
        for _, v in pairs(opts.e) do
            if (string.match(callerid_number, v)) then
                check_exit = true
                break
            end
        end
    end

    if (check_exit) then
        freeswitch.consoleLog("NOTICE", "[silence_detect] Callerid  " .. callerid_number .. " is matched excluded options")
        do return end
    end   

    algo = opts.a or 'samples'
    loop_count = opts.l or 5
    transfer_on_silence = opts.t or 'hangup'
    ringback = opts.r or session:getVariable('ringback') or "%(2000,4000,440,480)"
    tmp_dir = opts.s or '/dev/shm/'
    -- Prepare args table to hold algo options only
    table.remove(args, 1)

    record_append = session:getVariable('RECORD_APPEND') or nil
    record_read_only = session:getVariable('RECORD_READ_ONLY') or nil
    record_stereo = session:getVariable('RECORD_STEREO') or nil

    local tmp_file_name = session:getVariable('call_uuid') or "tmp_file"
    local is_silence_detected

    tmp_file_name = tmp_dir .. tmp_file_name .. '_sil_det.wav'

    session:execute("set_zombie_exec")
    session:setVariable('RECORD_READ_ONLY', 'true')
    session:setVariable('RECORD_APPEND', 'false')
    session:setVariable('RECORD_STEREO', 'false')
    -- Answer the call
    session:answer()

    for i = 1, loop_count do
        if opts.v then
            freeswitch.consoleLog("NOTICE", "[silence_detect] Loop:" .. i .. ', algorythm is ' .. algo .. ' ' .. table.concat(args, " "))
        end
        session:execute("record_session", tmp_file_name)
        session:execute("playback", 'tone_stream://' .. ringback)
        session:execute("stop_record_session", tmp_file_name)

        -- Function to return true if is silence in file is detected
        is_silence_detected, silence_detect_debug_info = silence_detect_file(tmp_file_name, algo, args)
        if opts.d then
            session:setVariable("silence_detect_" .. algo .. "_" .. i, silence_detect_debug_info)
        end
        os.remove(tmp_file_name)
        if (is_silence_detected == false) then
            loop_detected = i
            break
        end
    end

    -- Restore variables
    session:execute("unset", "RECORD_READ_ONLY")
    if record_append then
        session:setVariable('RECORD_APPEND', record_append)
    end
    if record_read_only then
        session:setVariable('RECORD_READ_ONLY', record_read_only)
    end
    if record_stereo then
        session:setVariable('RECORD_STEREO', record_stereo)
    end

    if (is_silence_detected) then
        if opts.v then
            freeswitch.consoleLog("NOTICE", "[silence_detect] Silence is detected on loop " .. loop_detected .. ". Transferring to " .. transfer_on_silence)
        end
        if (transfer_on_silence == 'hangup') then
            session:execute("hangup")
        else
            local domain_name = session:getVariable('domain_name') or ""
            session:execute("transfer", transfer_on_silence .. " XML " .. domain_name)
        end
    end
    if opts.v then
        freeswitch.consoleLog("NOTICE", "[silence_detect] Silence is not detected for call from " .. callerid_number)
    end
end