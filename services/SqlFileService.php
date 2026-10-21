<?php
/** Split trusted deployment SQL without breaking quoted semicolons or escapes. */
declare(strict_types=1);
namespace app\services;
final class SqlFileService
{
    public function statements(string $sql): array
    {
        $result=[]; $buffer=''; $quote=null; $length=strlen($sql);
        for($i=0;$i<$length;$i++) {
            $char=$sql[$i]; $next=$sql[$i+1]??'';
            if($quote!==null) {
                $buffer.=$char;
                if($char==='\\' && $i+1<$length) { $buffer.=$sql[++$i]; continue; }
                if($char===$quote) { if($next===$quote) { $buffer.=$sql[++$i]; } else { $quote=null; } }
                continue;
            }
            if(($char==='-' && $next==='-' && ctype_space($sql[$i+2]??' ')) || $char==='#') { while($i<$length && $sql[$i]!=="\n") { $i++; } $buffer.="\n"; continue; }
            if($char==='/' && $next==='*') { $end=strpos($sql,'*/',$i+2); if($end===false) { throw new \RuntimeException('Unclosed SQL comment'); } $i=$end+1; $buffer.=' '; continue; }
            if(in_array($char,["'",'"','`'],true)) { $quote=$char; $buffer.=$char; continue; }
            if($char===';') { if(trim($buffer)!=='') { $result[]=trim($buffer); } $buffer=''; continue; }
            $buffer.=$char;
        }
        if($quote!==null) { throw new \RuntimeException('Unclosed SQL literal'); }
        if(trim($buffer)!=='') { $result[]=trim($buffer); }
        return $result;
    }
}
