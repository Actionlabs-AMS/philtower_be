<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InputSanitizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Sanitize input data
        $this->sanitizeInput($request);

        return $next($request);
    }

    /**
     * Sanitize input data
     *
     * @param Request $request
     * @return void
     */
    private function sanitizeInput(Request $request): void
    {
        $input = $request->all();
        
        // Fields to exclude from HTML encoding (passwords, tokens, etc.)
        $excludeFromHtmlEncoding = ['password', 'user_pass', 'password_confirmation', 'token', 'api_token', 'remember_me'];
        
        $sanitized = $this->recursiveSanitize($input, $excludeFromHtmlEncoding);
        
        // Replace the request data with sanitized data
        $request->replace($sanitized);
    }

    /**
     * Recursively sanitize array data
     *
     * @param mixed $data
     * @param array $excludeFromHtmlEncoding
     * @param string|null $currentKey
     * @return mixed
     */
    private function recursiveSanitize($data, array $excludeFromHtmlEncoding = [], ?string $currentKey = null)
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->recursiveSanitize($value, $excludeFromHtmlEncoding, $key);
            }
            return $result;
        }

        if (is_string($data)) {
            // Check if current key should be excluded from HTML encoding
            $shouldExclude = $currentKey && in_array(strtolower($currentKey), array_map('strtolower', $excludeFromHtmlEncoding));
            return $this->sanitizeString($data, $shouldExclude);
        }

        return $data;
    }

    /**
     * Sanitize string input
     *
     * @param string $input
     * @param bool $excludeFromHtmlEncoding
     * @return string
     */
    private function sanitizeString(string $input, bool $excludeFromHtmlEncoding = false): string
    {
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Remove excessive whitespace
        $input = preg_replace('/\s+/', ' ', $input);
        
        // Basic XSS protection (skip for password fields)
        if (!$excludeFromHtmlEncoding) {
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
        
        // Remove potential SQL injection patterns
        $patterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\balter\b.*\btable\b)/i',
            '/(\bcreate\b.*\btable\b)/i',
            '/(\bexec\b.*\b\()/i',
            '/(\bexecute\b.*\b\()/i',
            '/(\bscript\b.*\b\>)/i',
            '/(\biframe\b.*\b\>)/i',
            '/(\bobject\b.*\b\>)/i',
            '/(\bembed\b.*\b\>)/i',
            '/(\bform\b.*\b\>)/i',
            '/(\binput\b.*\b\>)/i',
            '/(\btextarea\b.*\b\>)/i',
            '/(\bselect\b.*\b\>)/i',
            '/(\boption\b.*\b\>)/i',
            '/(\bapplet\b.*\b\>)/i',
            '/(\bmeta\b.*\b\>)/i',
            '/(\blink\b.*\b\>)/i',
            '/(\bstyle\b.*\b\>)/i',
            '/(\btitle\b.*\b\>)/i',
            '/(\bhead\b.*\b\>)/i',
            '/(\bbody\b.*\b\>)/i',
            '/(\bhtml\b.*\b\>)/i',
            '/(\bxml\b.*\b\>)/i',
            '/(\bphp\b.*\b\>)/i',
            '/(\basp\b.*\b\>)/i',
            '/(\bjsp\b.*\b\>)/i',
            '/(\bcgi\b.*\b\>)/i',
            '/(\bperl\b.*\b\>)/i',
            '/(\bpython\b.*\b\>)/i',
            '/(\bruby\b.*\b\>)/i',
            '/(\bshell\b.*\b\>)/i',
            '/(\bbash\b.*\b\>)/i',
            '/(\bcmd\b.*\b\>)/i',
            '/(\bpowershell\b.*\b\>)/i',
            '/(\bwscript\b.*\b\>)/i',
            '/(\bcscript\b.*\b\>)/i',
            '/(\bvbscript\b.*\b\>)/i',
            '/(\bjavascript\b.*\b\>)/i',
            '/(\bvbscript\b.*\b\>)/i',
            '/(\bonload\b.*\b\>)/i',
            '/(\bonerror\b.*\b\>)/i',
            '/(\bonclick\b.*\b\>)/i',
            '/(\bonmouseover\b.*\b\>)/i',
            '/(\bonfocus\b.*\b\>)/i',
            '/(\bonblur\b.*\b\>)/i',
            '/(\bonchange\b.*\b\>)/i',
            '/(\bonsubmit\b.*\b\>)/i',
            '/(\bonreset\b.*\b\>)/i',
            '/(\bonkeydown\b.*\b\>)/i',
            '/(\bonkeyup\b.*\b\>)/i',
            '/(\bonkeypress\b.*\b\>)/i',
            '/(\bonmousedown\b.*\b\>)/i',
            '/(\bonmouseup\b.*\b\>)/i',
            '/(\bonmousemove\b.*\b\>)/i',
            '/(\bonmouseout\b.*\b\>)/i',
            '/(\bonmouseenter\b.*\b\>)/i',
            '/(\bonmouseleave\b.*\b\>)/i',
            '/(\boncontextmenu\b.*\b\>)/i',
            '/(\bondblclick\b.*\b\>)/i',
            '/(\bonabort\b.*\b\>)/i',
            '/(\bonbeforeunload\b.*\b\>)/i',
            '/(\bonerror\b.*\b\>)/i',
            '/(\bonhashchange\b.*\b\>)/i',
            '/(\bonload\b.*\b\>)/i',
            '/(\bonmessage\b.*\b\>)/i',
            '/(\bonoffline\b.*\b\>)/i',
            '/(\bononline\b.*\b\>)/i',
            '/(\bonpagehide\b.*\b\>)/i',
            '/(\bonpageshow\b.*\b\>)/i',
            '/(\bonpopstate\b.*\b\>)/i',
            '/(\bonresize\b.*\b\>)/i',
            '/(\bonscroll\b.*\b\>)/i',
            '/(\bonstorage\b.*\b\>)/i',
            '/(\bonunload\b.*\b\>)/i',
        ];
        
        foreach ($patterns as $pattern) {
            $input = preg_replace($pattern, '[BLOCKED]', $input);
        }
        
        return $input;
    }
}
