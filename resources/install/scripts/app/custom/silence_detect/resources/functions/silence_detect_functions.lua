function silence_detect_samples(samples) 

	-- Differece in 2 close samples to say, that change was done
	local silence_threshold = argv[5] and tonumber(argv[5]) or 100

	-- How many silence_threshold to consider, that it's silence and not false-positive
	local threshold_total_hits = argv[6] and tonumber(argv[6]) or 5

	local first_sample = samples[1]
	local hits = 0

	for i = 2, #samples do
		if (math.abs(first_sample - samples[i]) > silence_threshold) then
			if (hits >= threshold_total_hits) then
				return false, "Detect noise on sample " .. i
			end
			hits = hits + 1
		end
		first_sample = samples[i]
	end

	return true, "Silence is detected"
end

function silence_detect_lines(samples)
	local samples_length = #samples
	-- Assuming min line lenght is 1% of sample
	local min_line_lenght = math.round(samples_length / 100)

	-- Should be small here
	local silence_threshold = argv[5] and tonumber(argv[5]) or 5
	
	local line_peak_ratio = argv[6] and tonumber(argv[6]) or 90

	local line_length = 0
	local current_line_lenght = 0

	local prev_sample = samples[1]

	for i = 2, #samples do
		if (math.abs(prev_sample - samples[i]) <= silence_threshold) then
			-- Check if we are in the line. Not changing prev_sample here to avoid slow constant change
			current_line_lenght = current_line_lenght + 1
		else
			-- Line had ended
			if (current_line_lenght > min_line_lenght) then
				line_length = line_length + current_line_lenght
			end
			current_line_lenght = 0
			prev_sample = samples[i]
		end
	end

	local current_line_peak_ratio = math.round(line_length / samples_length)

	if (current_line_peak_ratio > line_peak_ratio) then
		return true, "Line/Peak ratio is " .. current_line_peak_ratio
	end
	return false, "Line/Peak ratio is " .. current_line_peak_ratio
end

function silence_detect_file(filename)

	local file_reader = wav.create_context(filename, 'r')
	
	if (file_reader == false) then
		return true
	end

    file_reader.set_position(0)

    -- Read only channel 1
    local samples = file_reader.get_samples(math.floor(file_reader.get_samples_per_channel()) - 1)[1]

	file_reader.close_context()
	
	local function_name = "silence_detect_" .. algo
	
	if (samples and _G[function_name])then
		return _G[function_name](samples)
	end
	return true
end