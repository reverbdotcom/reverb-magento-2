<?php
namespace Reverb\Io\Model\Io;
class File extends \Magento\Framework\Filesystem\Io\File
{
    /**
     * Open file in stream mode
     * For set folder for file use open method
     *
     * @param string $fileName
     * @param string $mode
     * @return bool
     */
    public function streamOpen($fileName, $mode = 'w+', $chmod = 0666)
    {
        $writeableMode = preg_match('#^[wax]#i', $mode);
        if ($writeableMode && !is_writeable($this->_cwd)) {
            throw new Exception('Permission denied for write to ' . $this->getFilteredPath($this->_cwd));
        }

        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', 1);
        }

        @chdir($this->_cwd);
        $this->_streamHandler = @fopen($fileName, $mode);
        @chdir($this->_iwd);
        if ($this->_streamHandler === false) {
            throw new \Exception('Error write to file ' . $this->getFilteredPath($fileName));
        }

        $this->_streamFileName = $fileName;
        $this->_streamChmod = $chmod;
        return true;
    }


    public function streamLock($exclusive = true, $should_block = false)
    {
        if (!$this->_streamHandler) {
            return false;
        }
        $this->_streamLocked = true;
        $lock = $exclusive ? LOCK_EX : LOCK_SH;

        if (!$should_block)
        {
            $lock = $lock | LOCK_NB;
        }

        return flock($this->_streamHandler, $lock);
    }

    public function getStream()
    {
        return $this->_streamHandler;
    }

    public function streamReadCsv($delimiter = ',', $enclosure = '"', $escape = '\\')
    {
        if (!$this->_streamHandler) {
            return false;
        }

        $csv_data = @fgetcsv($this->_streamHandler, 0, $delimiter, $enclosure, $escape);

        if (!is_array($csv_data))
        {
            return false;
        }

        $escape_and_enclosure = $escape.$enclosure;

        // Unescape the enclosures
        foreach ($csv_data as $index => $value)
        {
            $unescaped_value = str_replace($escape_and_enclosure, $enclosure, $value);
            $csv_data[$index] = $unescaped_value;
        }

        return $csv_data;
    }
}
