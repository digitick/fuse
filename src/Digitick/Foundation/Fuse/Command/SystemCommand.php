<?php


namespace Digitick\Foundation\Fuse\Command;


abstract class SystemCommand extends AbstractCommand
{
    private $commandLine;
    private $output;
    private $return;

    /**
     * SystemCommand constructor.
     * @param string $key
     * @param $commandLine
     */
    public function __construct($key, $commandLine)
    {
        parent::__construct($key);
        $this->commandLine = $commandLine;
    }

    public function run()
    {
        $line = exec(
            $this->commandLine,
            $this->output,
            $this->return
        );
        return $this->output;
    }

    /**
     * @return string
     */
    public function getCommandLine()
    {
        return $this->commandLine;
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return mixed
     */
    public function getReturn()
    {
        return $this->return;
    }


}