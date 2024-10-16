
<!--  -->

<!-- change to line 272 -->

 protected function flushOutputBuffer()
    {
        $lines = str($this->outputBuffer)->explode("\n");

        $this->outputBuffer = (string) $lines->pop();

        $lines
            ->map(fn($line) => trim($line))
            ->filter()
            ->each(function ($line) {
                if (str($line)->contains('Development Server (http')) {
                    if ($this->serverRunningHasBeenDisplayed === false) {
                        $this->serverRunningHasBeenDisplayed = true;

                        $this->components->info("Server running on [http://{$this->host()}:{$this->port()}].");
                        $this->comment('  <fg=yellow;options=bold>Press Ctrl+C to stop the server</>');

                        $this->newLine();
                    }

                    return;
                }

                if (str($line)->contains(' Accepted')) {
                    $requestPort = $this->getRequestPortFromLine($line);

                    $this->requestsPool[$requestPort] = [
                        $this->getDateFromLine($line),
                        $this->requestsPool[$requestPort][1] ?? false,
                        microtime(true),
                    ];
                } elseif (str($line)->contains([' [200]: GET '])) {
                    $requestPort = $this->getRequestPortFromLine($line);

                    $this->requestsPool[$requestPort][1] = trim(explode('[200]: GET', $line)[1]);
                } elseif (str($line)->contains('URI:')) {
                    $requestPort = $this->getRequestPortFromLine($line);

                    $this->requestsPool[$requestPort][1] = trim(explode('URI: ', $line)[1]);
                } elseif (str($line)->contains(' Closing')) {
                    $requestPort = $this->getRequestPortFromLine($line);

                    if (empty($this->requestsPool[$requestPort])) {
                        $this->requestsPool[$requestPort] = [
                            $this->getDateFromLine($line),
                            false,
                            microtime(true),
                        ];
                    }

                    [$startDate, $file, $startMicrotime] = $this->requestsPool[$requestPort];

                    // $formattedStartedAt = $startDate->format('Y-m-d H:i:s');
                    if ($startDate) {
                        $formattedStartedAt = $startDate->format('Y-m-d H:i:s');
                        [$date, $time] = explode(' ', $formattedStartedAt);
                    } else {
                        // Handle the case when $startDate is null (log, skip, or set a default date)
                        $formattedStartedAt = now()->format('Y-m-d H:i:s');
                        [$date, $time] = explode(' ', $formattedStartedAt);
                    }



                    unset($this->requestsPool[$requestPort]);

                    [$date, $time] = explode(' ', $formattedStartedAt);

                    $this->output->write("  <fg=gray>$date</> $time");

                    $runTime = $this->runTimeForHumans($startMicrotime);

                    if ($file) {
                        $this->output->write($file = " $file");
                    }

                    $dots = max(terminal()->width() - mb_strlen($formattedStartedAt) - mb_strlen($file) - mb_strlen($runTime) - 9, 0);

                    $this->output->write(' ' . str_repeat('<fg=gray>.</>', $dots));
                    $this->output->writeln(" <fg=gray>~ {$runTime}</>");
                } elseif (str($line)->contains(['Closed without sending a request', 'Failed to poll event'])) {
                    // ...
                } elseif (! empty($line)) {
                    if (str($line)->startsWith('[')) {
                        $line = str($line)->after('] ');
                    }

                    $this->output->writeln("  <fg=gray>$line</>");
                }
            });
    }

                      
<!-- change to line 370 -->
 protected function getDateFromLine($line)
    {
        $regex = env('PHP_CLI_SERVER_WORKERS', 1) > 1
            ? '/^\[\d+]\s\[([a-zA-Z0-9: ]+)\]/'
            : '/^\[([^\]]+)\]/';

        $line = str_replace('  ', ' ', $line);

        preg_match($regex, $line, $matches);

        // Check if $matches[1] exists before attempting to format
        if (isset($matches[1])) {
            return Carbon::createFromFormat('D M d H:i:s Y', $matches[1]);
        }

        // Return null or handle it as needed if no match is found
        return null;
    }
