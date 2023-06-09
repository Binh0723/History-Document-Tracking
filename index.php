<?php
include_once("bootstrap.php");
include_once("db_connect.php");
include_once("docs-util.php");
session_start();

$op = $_GET['op'];
$aid=NULL;
$password = NULL;
ob_start();
if ($op == "userLogin") {
  $_SESSION['uid'] = getUserID($db, $_POST['tfUser'], $_POST['tfPassword']); // form
  $id = $_SESSION['uid'];

  $op = "myTranslations";
  $password = $_POST['tfPassword'];
} else if ($op == "archiveLogin") {
    $_SESSION['aid'] = getArchiveID($db, $_POST['tfArchive'], $_POST['tfPassword']);
    $aid = $_SESSION['aid'];
    $password = $_POST['tfPassword'];
    $op="";
} else if ($op == "searchByTags") {
    $tags = preg_split("/[\s,]+/", $_POST['query']);
    $op = "performTagSearch";
} else if ($op == "logout") {
  unset($_SESSION['uid']);
  unset($_SESSION['aid']);
  $op = "";
} else if ($op == "deleteTags") {
    $tags = $_POST['cbTags'];
    if (isset($_SESSION['aid']) and
        checkOwnership($db, $_SESSION['aid'], $_GET['docID'])) {
        print("<p>Deleting tags (not really)</p>\n");
        deleteTags($db, $tags);
    } else {
        print("<script>
            alert(\"Error: must be archival owner of document to remove tags.\")
        </script>");
    }

    $op = "viewDocument";
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Document Tracker</title>
        <link rel="stylesheet" href="main.css">
         <STYLE>
.menuItem
{
	border: 1px solid white;
	background-color:DimGray;
	color: White;
	text-align: center;
	padding-top:10px;
	padding-bottom:10px;
}

.menuItem:hover
{
	background-color:DarkGray;
	color:White;	
}

.background-layer
{
    background-color:White;
    padding:20px;
}

</STYLE>
    </head>

    <body style="max-width: 700px;margin: auto;background-image: url('magna-carta.jpg');background-repeat: no-repeat;background-attachment: fixed;background-size: cover;">
    <div class='background-layer'>
        <header style="margin-bottom: 2em">
            <nav>
                <hr>
                <ul>
<?php
if(!isset($_SESSION['aid']) && !isset($_SESSION['uid']))
{
	showLoginButtons();
}
else
{
	showLogoutForm();
}

?>
                  <li>
                  <h1 style="text-align:center">Document Tracker</h1>
                  </li>

                </ul>
                <hr>
            </nav>
        </header>


<?php

if(!isset($_SESSION['aid']) && !isset($_SESSION['uid']))
{
    print("<p>Welcome to Document Tracker, a service for tracking, transcribing,
        and translating historical documents from archives around the world!
        Sign in as a researcher or archivist to upload documents or contribute
        translations and transcriptions.</p>\n");
}


$aid = $_SESSION['aid'];
$id=$_SESSION['uid'];

if($id != NULL)
{
	showingMenuUser();
}
if($aid != NULL && $op != "viewDocument")
{
	showAddingDocumentsForm();
	showArchive($db, $aid);
	showAddingTag($db,$aid);
}

if ($op == "searchBar") {
  showSearchBar();
} else if ($op == "showUserLogin") {
    showUserLoginForm();
} else if ($op == "showArchiveLogin") {
    showArchiveLoginForm();
} else if ($op == "ShowMyTranslation") {
  showTranslations($db, $id);
}
else if($op=="ShowSearchByName")
{
	showSearchByNameForm();
}
else if($op=="addingDocuments")
{
	addingDocuments($db, $_POST,$aid);
	$op="";
}
else if($op=="ShowSearchByTag")
{
    searchByTags();
} else if ($op == "performTagSearch") {
    // Get tag IDs in an array
    $tagID = array();
    for ($i = 0; $i < count($tags); ++$i) {
        $tagID[$i] = getTagID($db, $tags[$i]);
    }

    // Search for and show documents
    if ($_POST['query-type'] == 'and') {
        $documents = getTaggedAll($db, $tagID);
    } else if ($_POST['query-type'] == 'or') {
        $documents = getTaggedAny($db, $tagID);
    }

    print("<p><b>Results:</b></p>");
    showDocuments($documents);
} else if ($op == "viewDocument") {
    $docID = $_GET['docID'];
    viewDocument($db, $docID);
}

if($op=="ShowAddingTranslation")
{
	showAddingtranslationForm();
}
if($op=="addingtranslation")
{
	addingTranslation($db, $_POST,$id);
}
if($op=="searchingDoc")
{
	showSearchByName($db, $_POST);
}


?>

    </div>
</body>
</html>
