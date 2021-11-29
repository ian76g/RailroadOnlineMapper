<?php

class Colors {
    private string $db_file = 'colors.db';
    private $db;

    public function __construct() {
        if (!file_exists($this->db_file)) {
            touch($this->db_file);
            $this->db = array();
        } else {
            $this->db = unserialize(file_get_contents($this->db_file));
        }

        if ($this->db === false) {
            $this->db = array();
        }
    }

    public function store_color(array $colors, string $code=null): string {
        if (!$code) {
            foreach ($this->db as $key => $item) {
                if ($item === $colors) {
                    return $key;
                }
            }
        }

        if ($code) {
            $key = $code;
        } else {
            $key = $this->random_key();
        }
        $this->db[$key] = $colors;
        file_put_contents($this->db_file, serialize($this->db));
        return $key;
    }

    public function get_color(string $code): ?array {
        if (array_key_exists($code, $this->db)) {
            return $this->db[$code];
        }
        return null;
    }

    private function random_key(int $len = 8): string {
        $word = array_merge(range('a', 'z'), range('A', 'Z'));
        shuffle($word);
        return substr(implode($word), 0, $len);
    }
}
