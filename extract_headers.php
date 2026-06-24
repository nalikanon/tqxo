<?php
$u = new SplFileObject('storage/app/private/uploads/UD-23-04-2026-SVO-A_B_C_1782279801_Sh_U.csv', 'r');
$u->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
$u->seek(0);
$u_head1 = $u->current();
$u->seek(1);
$u_head2 = $u->current();

$d = new SplFileObject('storage/app/private/uploads/UD-23-04-2026-SVO-A_B_C_1782279801_Sh_D.csv', 'r');
$d->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
$d->seek(0);
$d_head1 = $d->current();
$d->seek(1);
$d_head2 = $d->current();

file_put_contents('headers.json', json_encode([
    'u1' => $u_head1,
    'u2' => $u_head2,
    'd1' => $d_head1,
    'd2' => $d_head2
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
