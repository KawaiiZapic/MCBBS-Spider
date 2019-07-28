<?php
$myhost = "localhost";
$myuser = "localuser";
$mypass = "butidonthavepassword";
$mydb = "mcbbs";
$myport = 3306;
$t_prefix = "mb_";
$maxuid = 2890000;
$con = mysqli_connect($myhost, $myuser, $mypass, $mydb, $myport);
if (mysqli_connect_errno($con)) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
preload($con);
$api_url = "http://www.mcbbs.net/api/mobile/index.php?module=profile&uid=";
$data = array();
while (true) {
    $stime = msectime();
    $uid = getuid($con);
    if ($uid > $maxuid) {
        echo "\nDone!";
        exit;
    }
    echo "UID" . $uid . ":Finished in ";
    $raw = file_get_contents($api_url . $uid);
    $data = json_decode($raw, true)['Variables']['space'];
    if (empty($data)) {
        $etime = msectime();
        $ttime = $etime - $stime;
        echo "{$ttime}ms    Space not exists!\n";
        continue;
    } elseif (@$data['posts'] == NULL) {
        $etime = msectime();
        $ttime = $etime - $stime;
        echo "{$ttime}ms    Invaild user!\n";
        continue;
    }
    $ex1 = $data['extcredits1'];
    $ex2 = $data['extcredits2'];
    $ex3 = $data['extcredits3'];
    $ex4 = $data['extcredits4'];
    $ex5 = $data['extcredits5'];
    $ex6 = $data['extcredits6'];
    $ex7 = $data['extcredits7'];
    $ex8 = $data['extcredits8'];
    $posts = $data['posts'];
    $threads = $data['threads'];
    $friends = $data['friends'];
    $credits = $data['credits'];
    $groupid = $data['groupid'];
    $groupfriendlyname = addslashes($data['group']['grouptitle']);
    $grouptype = addslashes($data['group']['type']);
    $exgroupids = addslashes($data['extgroupids']);
    if ($exgroupids == "") {
        $exgroupids = "无";
    }
    $regdate = addslashes(date("Y-m-d", strtotime($data['regdate'])));
    $username = addslashes($data['username']);
    $friendlyregdate = date("Ym", strtotime($data['regdate']));
    mysqli_query($con, "insert into {$t_prefix}regdatestats (date,count) values ({$friendlyregdate},1) on duplicate key update count=count+1");
    if ($data['medals'] == "") {
        $medals = 0;
    } else {
        $medals = count($data['medals']);
        foreach ($data['medals'] as $mds) {
            mysqli_query($con, "insert into {$t_prefix}medalstats (mid,friendlyname,count) values ({$mds['medalid']},\"{$mds['name']}\",1) on duplicate key update count=count+1");
        }
    }
    $query = "insert into {$t_prefix}userstats (uid,username,credits,ex1,ex2,ex3,ex4,ex5,ex6,ex7,ex8,posts,threads,friends,medalscount,ugroup,exgroupids,regdate) values ({$uid},\"{$username}\",{$credits},{$ex1},{$ex2},{$ex3},{$ex4},{$ex5},{$ex6},{$ex7},{$ex8},{$posts},{$threads},{$friends},{$medals},\"{$groupfriendlyname}\",\"{$exgroupids}\",\"{$regdate}\") on duplicate key update uid=uid";
    mysqli_query($con, "insert into {$t_prefix}groupstats (groupid,friendlyname,grouptype,groupcredits,count,ex1,ex2,ex3,ex4,ex5,ex6,ex7,ex8,posts,threads,friends,medalscount) values ({$groupid},\"{$groupfriendlyname}\",\"{$grouptype}\",{$credits},1,{$ex1},{$ex2},{$ex3},{$ex4},{$ex5},{$ex6},{$ex7},{$ex8},{$posts},{$threads},{$friends},{$medals}) on duplicate key update count=count+1, ex1=ex1+{$ex1}, ex2=ex2+{$ex2}, ex3=ex3+{$ex3}, ex4=ex4+{$ex4}, ex5=ex5+{$ex5}, ex6=ex6+{$ex6}, ex7=ex7+{$ex7}, ex8=ex8+{$ex8}, posts=posts+{$posts}, threads=threads+{$threads}, friends=friends+{$friends}, medalscount=medalscount+{$medals}");
    mysqli_query($con, "update {$t_prefix}globalstat set credits=credits+{$credits},ex1=ex1+{$ex1}, ex2=ex2+{$ex2}, ex3=ex3+{$ex3}, ex4=ex4+{$ex4}, ex5=ex5+{$ex5}, ex6=ex6+{$ex6}, ex7=ex7+{$ex7}, ex8=ex8+{$ex8}, posts=posts+{$posts}, threads=threads+{$threads}, friends=friends+{$friends}, medalscount=medalscount+{$medals}");
    $write = mysqli_query($con, $query);
    echo mysqli_error($con);
    $etime = msectime();
    $ttime = $etime - $stime;
    if (!$write) {
        echo "{$ttime}ms    Fail\n";
    } else {
        echo "{$ttime}ms    OK!\n";
    }
}
function msectime()
{
    list($msec, $sec) = explode(' ', microtime());
    $msectime = (float) sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    return $msectime;
}
function preload($con)
{
    mysqli_query($con, "CREATE table if not exists {$t_prefix}uid(id int primary key,uid int)");
    mysqli_query($con, "CREATE table if not exists {$t_prefix}regdatestats(date int primary key,count int)");
    mysqli_query($con, "CREATE table if not exists {$t_prefix}medalstats(mid int primary key,friendlyname text,count int)");
    mysqli_query($con, "CREATE table if not exists {$t_prefix}userstats(uid int primary key,username text,credits int,ex1 int,ex2 int,ex3 int,ex4 int,ex5 int,ex6 int,ex7 int,ex8 int,posts int,threads int,friends int,medalscount int,ugroup text,exgroupids text,regdate text)");
    mysqli_query($con, "CREATE table if not exists {$t_prefix}globalstat(id int primary key,credits long,ex1 long,ex2 long,ex3 long,ex4 long,ex5 long,ex6 long,ex7 long,ex8 long,posts long,threads long,friends long,medalscount long)");
    mysqli_query($con, "CREATE table if not exists {$t_prefix}groupstats(groupid int primary key,friendlyname text,grouptype text,groupcredits long,count int,ex1 long,ex2 long,ex3 long,ex4 long,ex5 long,ex6 long,ex7 long,ex8 long,posts long,threads long,friends long,medalscount long)");
    mysqli_query($con, "INSERT {$t_prefix}uid (id,uid) VALUES (1,1) on duplicate key update uid=uid ");
    mysqli_query($con, "insert {$t_prefix}globalstat (id,credits,ex1,ex2,ex3,ex4,ex5,ex6,ex7,ex8,posts,threads,friends,medalscount) values (1,0,0,0,0,0,0,0,0,0,0,0,0,0) on duplicate key update credits=credits");
}
function getuid($con)
{
    mysqli_query($con, "start transaction");
    mysqli_query($con, "update {$t_prefix}uid set uid=uid+1");
    $uid = mysqli_query($con, "select uid from {$t_prefix}uid");
    $uid = mysqli_fetch_assoc($uid)['uid'] - 1;
    mysqli_query($con, "commit;");
    return $uid;
}
