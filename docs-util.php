<?php

include_once("db_connect.php");
include_once("bootstrap.php");
function showUserLoginForm() {
    $form = "
    <form name='login' method='POST' action='?op=userLogin'>
    <input type='text' name='tfUser' placeholder='username' />
    <input type='password' name='tfPassword' placeholder='password' />
    <input type='submit' value='Login' />
    </form>";
    
    print($form);
}

function showArchiveLoginForm() {
    $form = "
    <form name='login' method='POST' action='?op=archiveLogin'>
    <input type='text' name='tfArchive' placeholder='username' />
    <input type='password' name='tfPassword' placeholder='password' />
    <input type='submit' value='Login' />
    </form>";
    
    print($form);
    
}

function showLoginButtons() {
    $form = "<li style='float:right'>
        <form name='login' method='POST' action='?op=showArchiveLogin'>
        <input type='submit' value='Archivist Sign-in' />
        </form>
        </li>
        <li style='float:right'>
        <form name='login' method='POST' action='?op=showUserLogin'>
        <input type='submit' value='User Sign-in' />
        </form>
    </li>";

    print($form);
}

/*
 * Gets documents with one of a number of tags
 */
function getTaggedAny($db, $tags) {

    $query_string =
        "select distinct *
         from documents
         join tags on documents.docID = tags.docID
         where tagID = " . $tags[0];

    for ($i = 1; $i < count($tags); ++$i) {
        $tag = $tags[$i];
        $query_string = $query_string . "\nor tagID = " . $tag;
        ++$i;
    }

    $query_string = $query_string . ";";

    $res = $db->query($query_string);
    $documents = array();
    $i = 0;
    if ($res != FALSE) {
        while ($row = $res->fetch()) {
            $documents[$i] = $row;
            ++$i;
        }
    }

    return $documents;
}

/*
 * Gets documents with every one of the given tags
 */
function getTaggedAll($db, $tags) {
    $tag = $tags[0];
    $query_string =
        "select distinct
            A.docID, A.author, name, archiveID, url, 
              A.upload_timestamp
         from documents as A
         where exists (select *
                from tags
                where tags.docID = A.docID
                and tags.tagID = $tag)"; 

    for ($i = 1; $i < count($tags); ++$i) {
        $tag = $tags[$i];
        $query_string = $query_string . "\nand exists (select *
                from tags
                where tags.docID = A.docID
                and tags.tagID = $tag)"; 
        ++$i;
    }

    $query_string = $query_string . ";";

    $res = $db->query($query_string);
    $documents = array();
    $i = 0;
    if ($res != FALSE) {
        while ($row = $res->fetch()) {
            $documents[$i] = $row;
            ++$i;
        }
    }

    return $documents;
}

// Fetch and display tags on a document
function showTags($db, $docID) {
    $query_string =
        "select *
         from tags
         natural join tag_names
         where docID = $docID;";

    $res = $db->query($query_string);

    $output = "
    <FORM name=\"fmDelTag\" method=\"POST\" action=\"?op=deleteTags&docID=$docID\">
    <TABLE border=\"1\" cellspacing=\"0\" cellpadding=\"5\">
    <TR>
    <TH>Tag</TH>
    <TH><INPUT type=\"submit\" value=\"Delete Selected Tags\" /></TH>
    </TR>";

    while ($row = $res->fetch()) {
        $name = $row['name'];
        $id = $row['id'];
        $row_string = "
            <TR>
            <TD>$name</TD>
            <TD><INPUT type='checkbox' name='cbTags[]' value='$id' /></TD>
            </TR>";

        $output = $output . $row_string;
    }

    $output = $output . "</TABLE></FORM>";
    print($output);
}

function viewDocument($db, $docID) {
    $query_string =
        "select *
         from documents
         where docID = $docID;";

    $res = $db->query($query_string);
    $document = $res->fetch();

    $archive = getArchiveName($db, $document['archiveID']);
    $upload_timestamp = strtotime($document['upload_timestamp']);
    $displaytimestamp = date("y-m-d l", $upload_timestamp);
    $url = $document['url'];

    //TODO add link to archive?
    //TODO Fix urls
    //TODO work out correct order to display these in
    $output = "<p><b>Document:</b> " . $document['name'] . "</p>\n"
            . "<p><b>Archive:</b> " . $archive . "</p>\n"
            . "<p><b>Publication Date:</b> " . $document['time_published'] . "</p>\n"
            . "<p><b>Author:</b> " . $document['author'] . "</p>\n"
            . "<p><b>Language:</b> " . $document['language'] . "</p>\n"
            . "<p><b>Date Uploaded:</b> " . $displaytimestamp . "</p>\n"
            . "<p><a href=\"$url\"
                target=\"_blank\" rel=\"noopener noreferrer\">
                Read Document</a></p>";

    print($output);

    //TODO only make deletable if archive owner of document
    // print tags
    print("<p><b>Document Tags:</b></p>\n");
    showTags($db, $docID);
    // print translations
    print("<p><b>Available Translations:</b></p>\n");
    $translations = getTranslations($db, $docID);
    displayTranslations($db, $translations);
}

function deleteTags($db, $tags) {
    $query_string =
        "delete from tags
         where id in (";

    for ($i = 0; $i < count($tags); ++$i) {
        $tag = $tags[$i];
        $query_string = $query_string .
        "$tag,";
    }

    $query_string = rtrim($query_string, ",");
    
    $query_string = $query_string . ");";

    $db->query($query_string);
}

function checkOwnership($db, $aid, $docID) {
    $query_string =
        "select archiveID
         from documents
         where docID = $docID;";

    $res = $db->query($query_string);
    $row = $res->fetch();
    return ($row != NULL and $row['archiveID'] == $aid);
}

function getTranslations($db, $docID) {
    $query_string =
        "select *
         from translations
         where docID = $docID;";
    
    $res = $db->query($query_string);

    $translations = array();

    $i = 0;
    if ($res != FALSE) {
        while ($row = $res->fetch()) {
            $translations[$i] = $row;
            ++$i;
        }
    }

    return $translations;
}

function showDocuments($documents) {
    print("<TABLE border='1' cellspacing='0' cellpadding='5' style='margin-bottom:1cm;'>
    <tr>
    <TH>Document</TH>
    <TH>Author</TH>
    <TH>Link</TH>
    <TH>Upload Date</TH>
    </tr>");
    for ($i = 0; $i < count($documents); ++$i) {
        $archiveID = $documents[$i]['archiveID'];
        $author = $documents[$i]['author'];
        $docName = $documents[$i]['name'];
        $timestamp = strtotime($documents[$i]['upload_timestamp']);
        $url = $documents[$i]['url'];

        $displayTimestamp = date("Y-m-d l", $timestamp);

            // create a string with 1 HTML row
            $tr = "<tr>"
                . "<td>$docName</td>"
                . "<td>$author</td>"
                . "<td><a href=\"$url\"
                    target=\"_blank\" rel=\"noopener noreferrer\">
                    View Document</a></td>"
                . "<td>$displayTimestamp</td>"
                . "</tr>";

            printf("$tr\n");
    }

    print("</TABLE>");

}

function displayTranslations($db, $translations) {
    print("<table border='1' cellspacing='0' cellpadding='5' style='margin-bottom:1cm;'>
    <tr>
    <th>Translator</th>
    <th>Language</th>
    <th>URL</th>
    <th>Upload Date</th>
    </tr>");

    for ($i = 0; $i < count($translations); ++$i) {
        $language = $translations[$i]['language'];
        $url = $translations[$i]['url'];
        $upload_timestamp = strtotime($translations[$i]['upload_timestamp']);
        $displaytimestamp = date("y-m-d l", $upload_timestamp);

        $uid = $translations[$i]['translatorID'];
        $names = getNames($db, $uid);

        $fname = $names['fname'];
        $lname = $names['lname'];


        // create a string with 1 html row
        $tr = "<tr>"
            . "<td>$fname $lname</td>"
            . "<td>$language</td>"
            . "<td><a href=\"$url\"
                target=\"_blank\" rel=\"noopener noreferrer\">
                Read Translation</a></td>"
            . "<td>$displaytimestamp</td>"
            . "</tr>";


        printf("$tr \n");
    }

    print("</table>\n");
}

function showSearchBar() {
    $form = "<div id='searchbar'>
        <form id='searchbar'>
          <label for='query-type'>Search for: </label>
          <select name='query-type' id='query-type'>
            <option value='documents'>Documents</option>
            <option value='translations'>Translations</option>
            <option value='translators'>Translators</option>
            <option value='archives'>Archives</option>
          </select> 
          <input type='search' id='query' name='query' required>
        </form>
        </div>";

    
    print($form);
}

function searchByTags() {
    $form = "<div id='searchbar'>
        <form id='searchbar' method='POST' action='?op=searchByTags'>
          <label for='query-type'>Search for documents: </label>

          <select name='query-type' id='query-type'>
            <option value='or'>Matching any tag</option>
            <option value='and'>Matching all tags</option>
          </select> 
          <input type='search' id='tags' name='query' required>
        </form>
        </div>";

    
    print($form);
}

function getTagID($db, $tag) {
    $query_string = "select tagID
                from tag_names
                where name = '$tag';";
    
    $res = $db->query($query_string);
    $row = $res->fetch();
    return $row['tagID'];
}


function showLogoutForm() {
    $form = "
    <form name='login' method='POST' action='?op=logout'>
    <input type='submit' value='Logout' />
    </form>";
    
    print($form);
}

function getNames($db, $id) {
    $query_string =
        "select fname, lname
         from users
         where userID = $id;";
    $res = $db->query($query_string);
    $row = $res->fetch();

    return $row;
}

function getUserID($db, $email,$password) {
    $query_string =
        "select *
         from users
         where email = \"$email\" and password = '$password';";
    $res = $db->query($query_string);
    $row = $res->fetch();
    $id = $row['userID'];

    return $id;
}

function getArchiveID($db, $email,$password)
{
	$str = "SELECT archiveID FROM archive WHERE email = '$email' AND password='$password'";
	$res = $db->query($str);
	$row = $res->fetch();
	$aid = $row['archiveID'];
	return $aid;
}

function getArchiveName($db, $id)
{
	$str = "SELECT name FROM archive WHERE archiveID = $id";
	$res = $db->query($str);
	$row = $res->fetch();
	$name = $row['name'];
	return $name;
}


function showTranslations($db, $userID) {
    print("<table border='1' cellspacing='0' cellpadding='5' style='margin-bottom:1cm;'>
    <tr>
    <th>document</th>
    <th>archive</th>
    <th>translator</th>
    <th>translation date</th>
    </tr>");

    $query_string =
      "select documents.name as docname,
              documents.docID as docID,
              archive.name as archivename,
              users.fname as fname,
              users.lname as lname,
              unix_timestamp(translations.upload_timestamp) as time
      from users
      join translations on users.userid = translations.translatorid
      join documents on translations.docid = documents.docid
      join archive on documents.archiveid = archive.archiveid
      where translations.translatorid = $userID;";
    

    $res = $db->query($query_string);

    if ($res != null) {
        while ($row = $res->fetch()) {
            $docname = $row['docname'];
            $docID = $row['docID'];
            $archivename = $row['archivename'];
            $fname = $row['fname'];
            $lname = $row['lname'];
            $timestamp = $row['time'];
            $displaytimestamp = date("y-m-d l", $timestamp);

            // create a string with 1 html row
            $tr = "<tr>"
                ."<td><a href='index.php?op=viewDocument&&docID=$docID'>
                    $docname </a></td>\n"
                . "<td>$archivename</td>"
                . "<td>$fname $lname</td>"
                . "<td>$displaytimestamp</td>"
                . "</tr>";

            printf("$tr\n");
        }
    }

    print("</table>\n");
}

function logout() {
    print("<p>logging out.</p>");
}

function showarchive($db, $aid)
{
	$str = "select docid, name, url,time_published, language,author, upload_timestamp from documents where archiveid = $aid";
	$res = $db->query($str);
	$s = "<table border='1'>\n"
	     ."<tr>\n"
	     ."<th> document </th>\n"
	     // ."<th> link to the documents </th>\n"
	     ."<th> time published</th>\n"
	     ."<th>language</th>\n"
	     ."<th>author</th>\n"
	     ."<th> upload time </th>\n"
	     ."</tr>\n";
	printf("$s\n");
	while($row = $res->fetch())
	{
		$docid = $row['docid'];
		$name = $row['name'];
		$url = $row['url'];
		$time_published = $row['time_published'];
		$language = $row['language'];
		$author = $row['author'];
		$upload_timestamp= $row['upload_timestamp'];
    // TODO check viewdocument link and make it work
		$s1 = "<tr>\n"
		      //."<td>$name</td>\n"
		      ."<td><a href='index.php?op=viewDocument&&docID=$docid'> $name </a></td>\n"
		      //."<td><a href='?op=viewdocument&&docid=$docid'> $url</a></td>\n"
		      ."<td>$time_published</td>\n"
		      ."<td>$language</td>\n"
		      ."<td>$author</td>\n"
		      ."<td>$upload_timestamp</td>\n"
		      ."</tr>\n";
		 printf("$s1\n");
	}
	printf("</table>\n");
}

function showAddingTag($db,$aid)
{
	printf("<br>\n");
	printf("<br>\n");
	printf("<br>\n");
	$str = "SELECT docID, name, url,time_published, language,author, upload_timestamp FROM documents WHERE archiveID = $aid";
	$res = $db->query($str);
	printf("<FORM name='addingTag' method=POST action='?op=addingTag'/>\n");
	printf("<SELECT name='tagName'>");
	while($row=$res->fetch())
	{
		$docID = $row['docID'];
		$name = $row['name'];
		printf("<OPTION value='$docID'>$name</OPTION>\n");
	}
	printf("</SELECT>\n");
	
	$string = "<INPUT type='text' name='tfTagName' placeholder='Tag name'>\n"
		  ."<INPUT type='submit' value='addingTag'>\n";
	printf("$string\n");
	
	printf("<br>\n");
	printf("<br>\n");
	printf("<br>\n");
	
}

function addingTagDocument($db, $data)
{
	$docID = $data['tagName'];
	$tagName = $data['tfTagName'];
	$s = "SELECT * FROM tag_names WHERE name='$tagName'";
	$res = $db->query($s);
	$rowS = $res->fetch();
	$tagID = $rowS['tagID'];
	if($tagID != NULL)
	{
		$s1 = "SELECT * FROM tags WHERE tagID=$tagID AND docID = $docID";
		printf("$s1\n");
		$res = $db->query($s1);
		$r = $res->fetch();
		if($r['tagID'] == NULL)
		{
			printf("INSIDE if\n");
			$que = "INSERT INTO tags(tagID,docID) VALUE($tagID, $docID)";
			$res = $db->query($que);
			if($res != FALSE)
			{
				printf("<p>Sucessfully adding new tags</p>\n");
				header("refresh:3;url=index.php");
			}
		}
	}
	else
	{
		$str = "INSERT INTO tag_names(name) VALUE('$tagName')";
		$result1 = $db->query($str);
		$s = "SELECT * FROM tag_names WHERE name='$tagName'";
		$res = $db->query($s);
		$rowS = $res->fetch();
		$tagID = $rowS['tagID'];
		$que = "INSERT INTO tags(tagID,docID) VALUE($tagID,$docID)";
		$db->query($que);
		printf("<p>Sucessfully adding new tags</p>\n");
		header("refresh:3;url=index.php");
	}
	
}

function showAddingDocumentsForm()
{
	printf("<form name='fmadd' method=post action='?op=addingdocuments'/>\n");
	$str = "<input type='text' name='tfname' placeholder='document name'/>\n"
		."<input type='text' name='tfurl' placeholder='link to the document'/>\n"
		."<input type='text' name='tftimepub' placeholder='time published'/>\n"
		."<input type='text' name='tflanguage' placeholder='language'/>\n"
		."<input type='text' name='tfauthor' placeholder='author'/>\n"
		."<input type='submit' value='adding document'/>\n"
		."</form>\n";
	printf("$str\n");
}
function addingdocuments($db, $data,$aid)
{
	$aidd = $_session['aid'];
	$name= $data['tfname'];
	$url=$data['tfurl'];
	$timepublished=$data['tftimepub'];
	$language = $data['tflanguage'];
	$author=$data['tfauthor'];
	$time = date("y-m-d h:m:s");
	$que = "insert into documents(name,archiveid,url,time_published,language,author,upload_timestamp) value('$name',$aid,'$url','$timepublished','$language','$author','$time')";
	$res = $db->query($que);
	if($res != FALSE)
	{
		header("refresh:5;url=test.php");
		ob_end_flush();
	}
}

function showingMenuUser()
{
	$str =  "<DIV class='row' style='padding: 10px;margin-top:10p;margin-bottom:15p'>\n"
		."<A class='menuItem' href='?op=ShowAddingTranslation'><DIV class='col-lg-3'> Adding Translation</DIV></A>\n"
		."<A class='menuItem' href='?op=ShowMyTranslation'><DIV class='col-lg-3'> My Translation</DIV></A>\n"
		."<A class='menuItem' href='?op=ShowSearchByName'><DIV class='col-lg-3'> Search by name</DIV></A>\n"
		."<A class='menuItem' href='?op=ShowSearchByTag'><DIV class='col-lg-3'> Search By Tag</DIV></A>\n"
		."</DIV>\n";
		
	printf("$str\n");
}

function showAddingtranslationForm()
{

	printf("<br>\n");
	printf("<br>\n");
	printf("<form name='fmadd' method=post action='?op=addingtranslation'/>\n");
	$str = "<input type='text' name='tfName' placeholder='translation name'/>\n"
		."<input type='text' name='tfurl' placeholder='link to the document'/>\n"
		."<input type='text' name='tfdocName' placeholder='name of the original document'/>\n"
		."<input type='text' name='tflanguage' placeholder='language'/>\n"
		."<input type='submit' value='adding document'/>\n"
		."</form>\n";
	printf("$str\n");
}


function addingTranslation($db, $data,$id)
{

	$name = $data['tfName'];
	$url = $data['tfurl'];
	$document = $data['tfdocName'];
	$language = $data['language'];
	$time = date("y-m-d h:m:s");
	
	$str = "SELECT docID from documents WHERE name LIKE '$document'";
	
	$res = $db->query($str);
	
	$docID = $res->fetch()['docID'];
	printf("docID is $docID\n");
	$s = "INSERT INTO translations(url,docID,language,translatorID,upload_timestamp) VALUE('$url',$docID,'$language',$id,'$time')";
	printf("$s\n");
	$result = $db->query($s);
	if($result != FALSE)
	{
		header("refresh:5;url=test.php");
		ob_end_flush();
	}
	else
	{
		printf("<p> this is failure</p>\n");
	}
	
}

function showSearchByNameForm()
{
	$s = "<form name='search' method=post action='?op=searchingDoc'/>\n"
	     ."<input type='text' name='tfdocName' placeholder='document name'/>\n"
	     ."<input type='submit' value='search for document'/>\n"
	     ."</form>\n";
	printf("$s\n");
}

function showSearchByName($db, $data)
{
	$name = $data['tfdocName'];
	$str = "SELECT * FROM documents WHERE name LIKE '$name'";
	$res = $db->query($str);
	
	if($res != FALSE)
	{
		$s = "<TABLE border='1'>\n"
		."<tr>\n"
		."<th> Document name</th>\n"
		."<th> Url</th>\n"
		."<th> Time published</th>\n"
		."<th> Language</th>\n"
		."<th> Author</th>\n"
		."</tr>\n";
		
		print($s);
		$row = $res->fetch();
		
		$documentName = $row['name'];
		$url = $row['url'];
		$time_published=$row['time_published'];
		$language=$row['language'];
		$author=$row['author'];
		
		$str= "<tr>\n"
		      ."<td>$documentName</td>\n"
		      ."<td> $url</td>\n"
		      ."<td>$time_published</td>\n"
		      ."<td> $language</td>\n"
		      ."<td> $author</td>\n"
		      ."</tr>\n"
		      ."</TABLE>\n";
		print($str);	
	}
}		
?>
