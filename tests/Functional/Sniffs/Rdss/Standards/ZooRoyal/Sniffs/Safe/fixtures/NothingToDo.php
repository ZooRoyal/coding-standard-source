<?php

use function Safe\json_encode;

class NothingToDo
{
    public function a(string $b): void
    {
        array_merge(['w'], ['d']);
        $this->b();
        self::file_get_contents();
        $this->scandir();
        json_encode('asd');
    }

    private function b(): void
    {
        3 + 4;
    }
    private function scandir(): void
    {
        'w' . 'scandir()';
    }

    private static function file_get_contents(): void
    {
        'w' . 'file_get_contents()';
    }
}
