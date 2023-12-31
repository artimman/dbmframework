    <!-- Page Wrapper -->
    <div id="wrapper">
        <?php
            include('../application/View/_include/panel_sidebar.html.php');
        ?>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <?php
                    include('../application/View/_include/panel_topbar.html.php');
                ?>
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb bg-transparent">
                                <li class="breadcrumb-item"><h1 class="h3 mb-0 text-gray-800"><a href="<?php echo path('panel/managePage'); ?>" class="text-dark">Manage pages</a></h1></li>
                                <li class="breadcrumb-item active h3" aria-current="page"><?php echo $data['header']; ?></li>
                            </ol>
                        </nav>
                        <a href="<?php echo path('panel/createOrEditPage'); ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm font-weight-bold"><i class="fas fa-plus text-white-50 mr-2"></i>Create page</a>
                    </div>
                    <?php
                        // Panel flash messages
                        include('../application/View/_include/panel_messages.html.php');
                        // Form data fields
                        !empty($data['file']) ? $formFile = '?file=' . $data['file'] : $formFile = null;
                    ?>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="<?php echo path('panel/'. $data['action']); ?>" method="POST">
                                <div class="accordion" id="accordionFields">
                                    <div id="collapseFieldsOne" class="collapse collapseOpen" data-parent="#accordionFields">
                                        <?php if (empty($this->requestData('file'))) : ?>
                                        <div class="form-group">
                                            <label for="form_filename"><span class="font-weight-bold">File name</span>, the file name will be used in the website address, optimize the file name for search engines. Example: start with the string 'page-' and then file-name.</label>
                                            <input type="text" name="filename" id="form_filename" class="form-control" placeholder="Provide a file name, start with the string 'page-' and then file-name" value="" minlength="3" maxlength="100" pattern="[a-z\-]+" title="Only lowercase letters (a-z) and a dash (-)." required>
                                        </div>
                                        <?php endif; ?>
                                        <div class="form-group">
                                            <?php if (!empty($data['file'])) echo '<span class="float-right">File name: ' . $data['file'] . '</span>'; ?>
                                            <label for="form_keywords"><span class="font-weight-bold">Meta keywords</span>, separated by a comma</label>
                                            <input type="text" name="keywords" id="form_keywords" class="form-control" placeholder="Enter the website keywords" value="<?php if (!empty($data['fields'])) : echo $data['fields']->keywords; endif; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="form_description" class="font-weight-bold">Meta description</label>
                                            <input type="text" name="description" id="form_description" class="form-control" placeholder="Enter the website description" value="<?php if (!empty($data['fields'])) : echo $data['fields']->description; endif; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="form_title" class="font-weight-bold">Title and meta title</label>
                                            <input type="text" name="title" id="form_title" class="form-control" placeholder="Enter the website title" value="<?php if (!empty($data['fields'])) : echo $data['fields']->title; endif; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="formContent" class="font-weight-bold"><span class="mr-2" title="View remaining fields" data-toggle="tooltip" data-placement="top"><i id="iconUpDown" class="fas fa-angle-up text-primary" data-toggle="collapse" data-target="#collapseFieldsOne" aria-expanded="false" aria-controls="collapseFieldsOne"></i></span>HTML content</label>
                                    <textarea name="content" id="formContent" class="form-control" rows="15" onKeyDown="if(event.keyCode===9){var v=this.value,s=this.selectionStart,e=this.selectionEnd;this.value=v.substring(0, s)+'\t'+v.substring(e);this.selectionStart=this.selectionEnd=s+1;return false;}"><?php if (!empty($data['fields'])) : echo $data['fields']->content; endif; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <a href="<?php echo path('panel/createOrEditPage' . $formFile); ?>" class="btn btn-primary mr-md-2">Reload</a>
                                    <button type="button" id="previewContent" class="btn btn-primary mr-md-2">Preview</button>
                                    <button type="button" class="btn btn-primary mr-md-2" data-toggle="modal" data-target="#imagesModal">Images</button>
                                    <button type="submit" class="btn btn-primary text-uppercase"><?php echo $data['submit']; ?></button>
                                    <input type="hidden" name="file" value="<?php echo $data['file']; ?>">
                                </div>
                            </form>
                            <ul>
                                <li>You can use <span class="text-danger">HTML</span> code in content textarea (e.g. &lt;p&gt;, &lt;ul&gt; etc. and &lt;img src=&quot;{{url}}images/name.jpg&quot; class=&quot;img-fluid&quot; alt=&quot;Short description of the image&quot;&gt;).</li>
                                <li>You can use <span class="text-danger">{{url}}</span> code to add a direct url to the content (e.g. for an image address).</li>
                                <?php if (!empty($this->requestData('file'))) : ?>
                                    <li>Page address: <span class="text-danger"><?php echo htmlPageAddress($this->requestData('file')); ?></span></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End of Main Content -->
            <?php
                include('../application/View/_include/panel_footer.html.php');
            ?>
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->
    <?php
        include('../application/View/_include/panel_logout.html.php');
    ?>
