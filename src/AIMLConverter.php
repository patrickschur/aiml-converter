<?php

namespace AIMLConverter;

class AIMLConverter
{
    private $document = null;

    public function __construct()
    {
        $this->document = new \DOMDocument('1.0', 'UTF-8');

        $this->document->preserveWhiteSpace = false;
        $this->document->formatOutput = true;
    }

    /**
     * Creates a .CSV file from an AIML file
     *
     * @param string $filename Name of the AIML file
     * @return bool
     */
    public function aiml2csv($filename)
    {
        if (!is_readable($filename)) {
            throw new \InvalidArgumentException('File was not found or is not readable');
        }

        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if (!in_array($ext, ['xml', 'aiml']) ||
            mime_content_type($filename) !== 'application/xml'
        ) {
            throw new \InvalidArgumentException('Wrong MIME content type or file extension');
        }

        $this->document->load($filename);

        $csv = [];

        /** @var \DOMElement $category */
        foreach ($this->document->getElementsByTagName('category') as $line => $category)
        {
            $csv[$line][] = 0;

            $pattern = $that = $topic = $template = null;

            /** @var \DOMElement $childNode */
            foreach ($category->childNodes as $childNode) {
                $xml = $childNode->ownerDocument->saveXML($childNode);

                switch ($childNode->nodeName) {
                    case 'pattern':
                        /** @noinspection All */
                        $pattern = strip_tags($xml, '<set><bot><name>');
                        break;
                    case 'that':
                        /** @noinspection All */
                        $that = strip_tags($xml, '<set><bot><name>');
                        break;
                    case 'topic':
                        /** @noinspection All */
                        $topic = strip_tags($xml, '<set><bot><name>');
                        break;
                    case 'template':
                        $template = substr($xml, 10, -11);
                        break;
                }
            }

            $template = trim($template);
            $template = str_replace([PHP_EOL, ','], ['#Newline', '#Comma'], $template);

            $csv[$line][] = $pattern;
            $csv[$line][] = ($that !== null) ? $that : '*';

            if ($category->parentNode->nodeName === 'topic') {
                $csv[$line][] = $category->parentNode->getAttribute('name');
            }
            else {
                $csv[$line][] = ($topic !== null) ? $topic : '*';
            }

            $csv[$line][] = $template;
            $csv[$line][] = $filename;
        }

        $this->document->removeChild($this->document->firstChild);

        $filename = substr_replace($filename, 'csv', strpos($filename, $ext));
        $handle = fopen($filename, 'w');

        if (!$handle) {
            return false;
        }

        foreach ($csv as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return true;
    }

    /**
     * Creates a various amount of AIML files from a .CSV file
     *
     * @param string $filename Name of the CSV file
     * @return bool
     */
    public function csv2aiml($filename)
    {
        if (!is_readable($filename)) {
            throw new \InvalidArgumentException('File was not found or is not readable');
        }

        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'csv' ||
            !in_array(mime_content_type($filename), ['text/plain', 'text/csv'])
        ) {
            throw new \InvalidArgumentException('Wrong MIME content type or file extension');
        }

        /** @var \DOMElement[] $files */
        $files = [];

        foreach (file($filename, FILE_SKIP_EMPTY_LINES) as $row)
        {
            $row = explode(',', $row, 5);

            if (!isset($row[4])) {
                continue;
            }

            $tmp = explode(',', strrev($row[4]), 2);

            if (count($tmp) !== 2) {
                continue;
            }

            $row[4] = strrev($tmp[1]);
            $row[5] = strrev($tmp[0]);

            $row = array_map('trim', $row);

            if (!isset($files[$row[5]]))
            {
                $files[$row[5]] = $this->document->createElement('aiml');
                $files[$row[5]]->setAttribute('version', '2.0');
            }

            foreach ($row as &$entity)
            {
                $entity = htmlentities(trim($entity));
            }

            $category = $this->document->createElement('category');

            $category->appendChild($this->document->createElement('pattern', $row[1]));

            if ('*' !== $row[2]) {
                $category->appendChild($this->document->createElement('that', $row[2]));
            }

            if ('*' !== $row[3]) {
                $category->appendChild($this->document->createElement('topic', $row[3]));
            }

            $category->appendChild($this->document->createElement('template', $row[4]));

            $files[$row[5]]->appendChild($category);
        }

        $emptyTags = [
            'that', 'input', 'request', 'response',
            'date', 'sr', 'id', 'program', 'vocabulary',
            'bot', 'star', 'topicstar', 'thatstar', 'get'
        ];

        $emptyTags = implode('|', $emptyTags);

        foreach ($files as $filename => $aiml)
        {
            $this->document->appendChild($aiml);

            $data = $this->document->saveXML();
            $data = html_entity_decode($data);
            $data = str_replace(['#Comma', '#Newline'], [',', PHP_EOL], $data);

            $data = preg_replace('/(<(' . $emptyTags . ')[^>]+?)>(?!.*?<\/\2>)/', '\\1/>', $data);

            file_put_contents($filename, $data);

            $this->document->removeChild($aiml);
        }

        return true;
    }
}