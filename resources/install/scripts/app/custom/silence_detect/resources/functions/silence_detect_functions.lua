function silence_detect_in_file(file)
    if session:ready() then
        local file_duration = session:getVariable('record_ms')
        session:execute("wait_for_silence", "200 15 10 " .. file_duration .. " " .. file)
        session:execute("info")
    end
end