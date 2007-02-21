<?php response_header('Roadmap :: ' . clean($this->package)); ?>
<h1>Roadmap for Package <?php echo clean($this->package); ?></h1>
<a href="/bugs/search.php?package_name[]=<?php echo urlencode(clean($this->package)) ?>&status=Open">Bug Tracker</a>
<ul class="side_pages">
<?php foreach ($this->roadmap as $info): ?>
 <li class="side_page"><a href="#a<?php echo $info['roadmap_version'] ?>"><?php echo $info['roadmap_version'] ?></a> (<a href="roadmap.php?edit=<?php echo $info['id']
 ?>">edit</a>|<a href="roadmap.php?delete=<?php echo $info['id']
 ?>" onclick="return confirm('Really delete roadmap <?php echo $info['roadmap_version']
 ?>?');">delete</a>)</li>
<?php endforeach; ?>
 <li><a href="roadmap.php?package=<?php echo urlencode($this->package) ?>&new=1">New roadmap</a></li>
</ul>
<?php foreach ($this->roadmap as $info):
    $x = ceil((((strtotime($info['releasedate']) - time()) / 60) / 60) / 24);
?>
<a name="a<?php echo $info['roadmap_version'] ?>"></a>
<h2>Version <?php echo $info['roadmap_version'] ?></h2>
<table>
 <tr>
  <td>
   <a href="roadmap.php?package=<?php echo urlencode($this->package) ?>&addbugs=1&roadmap=<?php
    echo urlencode($info['roadmap_version']) ?>">Add Bugs/Features to this Roadmap</a>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Scheduled Release Date
  </th>
  <td class="form-input">
   <strong<?php if ($x < 0) echo ' class="lateRelease"' ?>><?php echo date('Y-m-d', strtotime($info['releasedate'])) .
                      ' (' . $x . ' day';
                 if ($x != 1) echo 's';
                 if ($x < 0) echo '!!'; ?>)</strong>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Release Goals
  </th>
  <td class="form-input">
   <pre><?php echo htmlspecialchars($info['description']); ?></pre>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Bugs
  </th>
  <td class="form-input">
   <?php echo $this->bugs[$info['roadmap_version']] ?>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Feature Requests
  </th>
  <td class="form-input">
   <?php echo $this->feature_requests[$info['roadmap_version']] ?>
  </td>
 </tr>
</table>
<?php endforeach; // foreach ($this->versions) ?>
<?php response_footer(); ?>