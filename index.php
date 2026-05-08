<?php namespace x\form;

function content($content) {
    if (!\is_array($form = $_SESSION['form'] ?? []) || 'text/html' !== \type()) {
        return;
    }
    if (false !== ($n = \strpos($content, '<input')) && \strspn($content, " \n\r\t", $n + 6)) {
    } else if (false !== ($n = \strpos($content, '<select')) && \strspn($content, " \n\r\t", $n + 7)) {
    } else if (false !== ($n = \strpos($content, '<textarea')) && \strspn($content, " \n\r\t", $n + 9)) {
    } else {
        return;
    }
    // Convert form name(s) like `asdf[]` to `asdf[0]`
    $fix = static function (string $name) {
        static $lot = [];
        $max = \strlen($name);
        $r = [];
        $s = "";
        for ($i = 0; $i < $max; ++$i) {
            $c = $name[$i];
            if ('[' === $c) {
                if ("" !== $s) {
                    $r[] = $s;
                }
                $s = "";
                continue;
            }
            if (']' === $c) {
                $r[] = $s;
                $s = "";
                continue;
            }
            $s .= $c;
        }
        if ("" !== $s) {
            $r[] = $s;
        }
        $next = static function (array $keys) use (&$lot) {
            $r =& $lot;
            foreach ($keys as $key) {
                if (!isset($r[$key])) {
                    $r[$key][\P] = 0;
                }
                $r =& $r[$key];
            }
            return $r[\P]++;
        };
        $c = [];
        $new = [];
        foreach ($r as $v) {
            $c[] = $new[] = $v = "" === $v ? $next($c) : $v;
        }
        $r = [$s = \array_shift($new) . "", \strtr($s, ['.' => "\\."])];
        foreach ($new as $k) {
            $r[0] .= '[' . $k . ']';
            $r[1] .= '.' . \strtr($k . "", ['.' => "\\."]);
        }
        return $r;
    };
    $r = "";
    foreach (\apart($content, [
        'script', // Need the full token to skip
        'select', // Need the full token to modify its option(s)
        'style', // Need the full token to skip
        'textarea' // Need the full token to modify its content
    ], ['input']) as $v) {
        if (1 !== $v[1] && 2 !== $v[1]) {
            $r .= $v[0];
            continue;
        }
        if ('/' === ($n = \substr($v[0], 1, \strcspn($v[0], " \n\r\t>", 1)))[0] ?? 0) {
            $r .= $v[0];
            continue;
        }
        if ('input' === $n) {
            $e = new \HTML($v[0]);
            $e = [$e[0], $e[1], $e[2]];
            if (!$name = ($e[2]['name'] ?? 0)) {
                $r .= $v[0];
                continue;
            }
            $type = $e[2]['type'] ?? 'text';
            if ('file' === $type || 'hidden' === $type || 'password' === $type) {
                // Disable form session on `file`, `hidden` and `password` input
                $r .= $v[0];
                continue;
            }
            $c = $fix($name);
            $e[2]['name'] = $c[0];
            if (null !== ($value = \get($form, $c[1]))) {
                if ('checkbox' === $type || 'radio' === $type) {
                    $e[2]['checked'] = \s($value) === \s($e[2]['value'] ?? \P);
                } else {
                    $e[2]['value'] = \s($value);
                }
            }
            $r .= new \HTML($e, true);
            continue;
        }
        if ('select' === $n) {
            $e = new \HTML($v[0], 1);
            $e = [$e[0], $e[1], $e[2]];
            if (!$name = ($e[2]['name'] ?? 0)) {
                $r .= $v[0];
                continue;
            }
            $c = $fix($name);
            $e[2]['name'] = $c[0];
            if (null !== ($value = \get($form, $c[1]))) {
                if (!empty($e[2]['multiple'])) {
                    // TODO
                }
                foreach ($e[1] as &$f) {
                    if ('option' !== ($f[0] ?? 0)) {
                        continue;
                    }
                    $f[2]['selected'] = \s($value) === \s($f[2]['value'] ?? $f[1] ?? \P);
                }
                unset($f);
            }
            $r .= new \HTML($e, true);
            continue;
        }
        if ('textarea' === $n) {
            $e = new \HTML($v[0]);
            $e = [$e[0], $e[1], $e[2]];
            if (!$name = ($e[2]['name'] ?? 0)) {
                $r .= $v[0];
                continue;
            }
            $c = $fix($name);
            $e[2]['name'] = $c[0];
            if (null !== ($value = \get($form, $c[1]))) {
                $e[1] = \htmlspecialchars(\s($value));
            }
            $r .= new \HTML($e, true);
            continue;
        }
        $r .= $v[0];
    }
    return $r;
}

function let() {
    unset($_SESSION['form']);
}

\Hook::set('content', __NAMESPACE__ . "\\content", 0);
\Hook::set('let', __NAMESPACE__ . "\\let", 20);