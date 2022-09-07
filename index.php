<?php namespace x\form;

function content($content) {
    $session = $_SESSION[\State::get('x.form.key') ?? 'form'] ?? [];
    if (!\is_array($session)) {
        return;
    }
    // Convert `foo[bar][baz]` to `foo.bar.baz`
    $keys = static function (string $in) {
        return \trim(\strtr($in, [
            '.' => "\\.",
            '][' => '.',
            '[' => '.',
            ']' => '.'
        ]), '.');
    };
    if (false !== \strpos($content, '<input ')) {
        $content = \preg_replace_callback('/<input(?:\s[^>]*)?>/', function ($m) use ($keys, $session) {
            $input = new \HTML($m[0]);
            if (!$name = $input['name']) {
                return $m[0];
            }
            $type = $input['type'];
            if ('file' === $type || 'hidden' === $type || 'password' === $type) {
                // Disable form session on `file`, `hidden` and `password` input
                return $m[0];
            }
            $name = $keys($name);
            $value = \get($session, $name);
            if ('checkbox' === $type || 'radio' === $type) {
                if (isset($value)) {
                    $input['checked'] = \s($value) === \s($input['value']);
                }
            } else {
                $input['value'] = $value ?? $input['value'];
            }
            return $input;
        }, $content);
    }
    if (false !== \strpos($content, '<select ')) {
        $content = \preg_replace_callback('/<select(?:\s[^>]*)?>[\s\S]*?<\/select>/', function ($m) use ($keys, $session) {
            $select = new \HTML($m[0]);
            if (!$name = $select['name']) {
                return $m[0];
            }
            $name = $keys($name);
            $value = \get($session, $name);
            $select[1] = \preg_replace_callback('/<option(?:\s[^>]*)?>[\s\S]*?<\/option>/', function ($m) use ($value) {
                $option = new \HTML($m[0]);
                if (isset($value)) {
                    $option['selected'] = \s($value) === \s($option['value'] ?? $option[1]);
                }
                return $option;
            }, $select[1]);
            return $select;
        }, $content);
    }
    if (false !== \strpos($content, '<textarea ')) {
        $content = \preg_replace_callback('/<textarea(?:\s[^>]*)?>[\s\S]*?<\/textarea>/', function ($m) use ($keys, $session) {
            $textarea = new \HTML($m[0]);
            if (!$name = $textarea['name']) {
                return $m[0];
            }
            $name = $keys($name);
            $value = \get($session, $name);
            $textarea[1] = \is_string($value) ? \htmlspecialchars($value) : $textarea[1];
            return $textarea;
        }, $content);
    }
    return $content;
}

function let() {
    unset($_SESSION[\State::get('x.form.key') ?? 'form']);
}

\Hook::set('content', __NAMESPACE__ . "\\content", 0);
\Hook::set('let', __NAMESPACE__ . "\\let", 20);