<?php

function findFiles($dir, $pattern)
{
  $results = [];
  $subfolders = [];

  // Open the folder (if possible) and start reading
  if(($dh = opendir($dir)) === false) { throw new \Exception("Couldn't open {$dir} for reading!"); }
  while(($entry = readdir($dh)) !== false)
  {
    // Skip special . and .. folders
    if(($entry == ".") || ($entry == "..")) { continue; }

    $full_entry = $dir . "/" . $entry;
    if(is_dir($full_entry))
    {
      // Subfolder found
      $subfolders[] = $full_entry;
    }
    else
    {
      // Optional filter - we'll only files that contain the substring we're looking for
      if(strpos($entry,$pattern) !== false)
      {
        // Record the file along with its last modified timestamp
        $results[$full_entry] = filemtime($full_entry); // <-- This is where I made the tweak
      }
    }
  }
  closedir($dh);

  // Recurse into subfolders and merge their results into this one
  foreach($subfolders as $subfolder)
  {
    $results = array_merge($results, findFiles($subfolder, $pattern));
  }

  // Return the results for this call
  return $results;
}

?>
