	<!-- Javascript Body Custom Added -->
	<!-- Modal Images -->
	<div id="imagesModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-xl" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title font-weight-bold">Uploaded images</h5>
					<button type="button" class="close" data-dismiss="modal">&times;</button>
				</div>
				<div class="modal-body">
					<div class="table-responsive" style="max-height:245px">
						<table class="table table-striped table-sm">
						<thead class="table-dark">
						<tr>
							<th scope="col" width="5%">#</th>
							<th scope="col">Address url and name</th>
							<th scope="col" width="25%" class="text-right">Action</th>
						</tr>
						</thead>
						<tbody id="rowTable">
						<?php if (!empty($data['images'])): $i=1; ?>
						<?php foreach ($data['images'] as $image): ?>
						<tr id="<?php echo 'row_id_' . $i; ?>">
							<th scope="row"><?php echo $i; ?></th>
							<td>
								<div class="dbm-css-tooltip">
									<span class="text-break"><?php echo '{{url}}images/blog/category/photo/' . $image; ?></span>
									<div class="tooltip-body">
										<img src="<?php echo path('images/blog/category/thumb/' . $image); ?>" class="img-fluid" alt="Insert to content">
									</div>
								</div>
							</td>
							<td class="text-right">
								<button class="btn btn-primary btn-sm setImageMain" title="Insert main image" data-text="<?php echo $image; ?>"><i class="fas fa-image"></i></button>
								<button class="btn btn-danger btn-sm ml-md-1 deleteImage" title="Delete" data-fid="<?php echo $i; ?>" data-file="<?php echo $image; ?>"><i class="fas fa-trash-alt"></i></button>
							</td>
						</tr>
						<?php $i++; ?>
						<?php endforeach; ?>
						<?php else: ?>
						<tr>
							<td colspan="3">You haven not uploaded any image.</td>
						<tr>
						<?php endif; ?>
						</tbody>
						</table>
					</div>
					<div class="mt-1 text-right small">* To reload the list, refresh the page using the &quot;Reload&quot; button.</div>
				</div>
				<div class="modal-footer">
					<h6 class="mr-auto">Add image</h6>
					<div class="w-100">
						<div id="formAlert"></div>
						<form method="post" enctype="multipart/form-data">
							<div class="form-group">
								<div class="input-group">
									<div class="custom-file small-custom-file">
										<input type="file" name="file" id="formFile" class="custom-file-input">
										<label for="formFile" class="custom-file-label small-custom-file-label">Choose file</label>
									</div>
								</div>
							</div>
							<div class="form-group">
								<button type="button" name="submit" class="btn btn-primary float-right" id="uploadImage"><i class="fas fa-upload mr-3"></i>Upload image</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- Preview Modal -->
	<div id="previewModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-xl" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="previewModalLabel">Page content</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<div id="previewBody"></div>
				</div>
				<div class="modal-footer">
					<span class="mr-auto small">* The styles in the preview may differ slightly from what actually appears on the landing page.</span>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>
	<!-- Custom JavaScript -->
	<script>
		$(document).ready(function() {
			$('#uploadImage').click(function() {
				$('#formAlert').html('').append('<div class="progress mb-3" style="height:20px;"><div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width:100%;" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">Please wait. Image loading...</div></div>');

				var params = new FormData();
				var files = $('#formFile')[0].files[0];
				var type = $('#formType').val();
				params.append('file', files);
				params.append('type', type);

				$.ajax({
					type: 'POST',
					url: '<?php echo APP_PATH; ?>panel/ajaxUploadImageSection',
					data: params,
					contentType: false,
					processData: false,
					success: function(response) {
						if (response != 0) {
							var alert = JSON.parse(response);

							$('#formAlert').html('').append('<div class="alert alert-' + alert['status'] + ' px-2 py-1">' + alert['message'] + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');

							if (alert['data'] !== undefined) {
								$('#rowTable').prepend('<tr><td class="font-weight-bold text-">#new</td><td colspan="2"><div class="dbm-css-tooltip"><span>{{url}}images/blog/category/photo/' + alert['data'] + '</span><div class="tooltip-body"><img src="<?php echo APP_PATH; ?>public/images/blog/category/thumb/' + alert['data'] + '" class="img-fluid" alt="Insert to content"></div></div></td></tr>');
								$(".custom-file-label").html('');
							}
						} else {
							alert('JavaScript: An unexpected response error occurred!');
						}
					},
					error: function() {
						alert('JavaScript: An unexpected error occurred!');
					}
				});
			});

			$('.deleteImage').click(function () {
				var fid = $(this).attr("data-fid");
				var file = $(this).attr("data-file");
				var params = 'file=' + file + '&type=blog';

				if (confirm('Are you sure you want to delete?')) {
					$.ajax({
						type: "GET",
						url: '<?php echo APP_PATH; ?>panel/ajaxDeleteImageSection',
						data: params,
						cache: false,
						contentType: false,
						processData: false,
						success: function(response) {
							if (response != 0) {
								var alert = JSON.parse(response);

								$('#row_id_' + fid).fadeOut();
								$('#formAlert').html('').append('<div class="alert alert-' + alert['status'] + ' px-2 py-1">' + alert['message'] + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
							} else {
								alert('JavaScript: An unexpected response error occurred!');
							}
						},
						error: function() {
							alert('JavaScript: An unexpected error occurred!');
						}
					});

					return false;
				}
			});

			$('.setImageMain').click(function() {
				$("#formImage").val($(this).attr("data-text"));
				$('#imagesModal').modal('hide');
			});

			$('[data-toggle="tooltip"]').tooltip();
		});

		$(".custom-file-input").on("change", function() {
			var fileName = $(this).val().split("\\").pop();

			$(this).siblings(".custom-file-label").addClass("selected").html(fileName);
		});

		$('#collapseOne').show();
		$('#collapseOne a:nth-child(5)').addClass("active");
	</script>
