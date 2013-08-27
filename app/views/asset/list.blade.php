@extends('layout.main')

@section('content')
@parent

<table id="assets" cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered table-hover">
	<thead>
		<tr>
			<th>Type</th>
			<th>Maker</th>
			<th>Product</th>
			<th>Role</th>
			<th>User</th>
			<th>Ownership</th>
			<th>Year</th>
			<th>Cost</th>
			<th>Lifespan</th>
			<th>Expected Replacement Cost</th>
		</tr>
	</thead>
	<tbody>
		@foreach($assets as $asset)
		<tr data-href="{{ URL::to('asset/'.$asset->id) }}">
			<td>{{ $asset->type->name }}</td>
			<td>{{ $asset->maker }}</td>
			<td>{{ $asset->product }}</td>
			<td>{{ is_null($asset->role) ? '<em>unassigned</em>' : $asset->role->name }}</td>
			<td>{{ is_null($asset->role) ? '<em>n/a</em>' : $asset->role->user }}</td>
			<td>{{ $asset->ownership }}</td>
			<td>{{ $asset->purchase_year }}</td>
			<td>{{ $asset->purchase_cost }}</td>
			<td>{{ $asset->lifespan.' '.str_plural('year', $asset->lifespan) }}</td>
			<td>{{ $asset->replace_cost }}</td>
		</tr>
		@endforeach
	</tbody>
</table>

<div id="asset-modal" class="modal hide fade" role="dialog" aria-labelledby="asset-modal-header" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="asset-modal-header">Edit Asset</h3>
  </div>
  <div class="modal-body">
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
    <button id="asset-save" class="btn btn-primary" data-loading-text="Saving...">Save Changes</button>
    <button id="asset-delete" class="btn btn-danger pull-left" data-loading-text="Deleting...">Delete Asset</button>
  </div>
</div>
@stop

@section('scripts')
<script>
	$.extend( $.fn.dataTableExt.oStdClasses, {
	  "sWrapper": "dataTables_wrapper form-inline"
	});

	/* Default class modification */
	$.extend( $.fn.dataTableExt.oStdClasses, {
		"sWrapper": "dataTables_wrapper form-inline"
	} );


	/* API method to get paging information */
	$.fn.dataTableExt.oApi.fnPagingInfo = function ( oSettings )
	{
		return {
			"iStart":         oSettings._iDisplayStart,
			"iEnd":           oSettings.fnDisplayEnd(),
			"iLength":        oSettings._iDisplayLength,
			"iTotal":         oSettings.fnRecordsTotal(),
			"iFilteredTotal": oSettings.fnRecordsDisplay(),
			"iPage":          oSettings._iDisplayLength === -1 ?
				0 : Math.ceil( oSettings._iDisplayStart / oSettings._iDisplayLength ),
			"iTotalPages":    oSettings._iDisplayLength === -1 ?
				0 : Math.ceil( oSettings.fnRecordsDisplay() / oSettings._iDisplayLength )
		};
	};

	/* Bootstrap style pagination control */
	$.extend( $.fn.dataTableExt.oPagination, {
		"bootstrap": {
			"fnInit": function( oSettings, nPaging, fnDraw ) {
				var oLang = oSettings.oLanguage.oPaginate;
				var fnClickHandler = function ( e ) {
					e.preventDefault();
					if ( oSettings.oApi._fnPageChange(oSettings, e.data.action) ) {
						fnDraw( oSettings );
					}
				};

				$(nPaging).addClass('pagination').append(
					'<ul>'+
						'<li class="prev disabled"><a href="#">&larr; '+oLang.sPrevious+'</a></li>'+
						'<li class="next disabled"><a href="#">'+oLang.sNext+' &rarr; </a></li>'+
					'</ul>'
				);
				var els = $('a', nPaging);
				$(els[0]).bind( 'click.DT', { action: "previous" }, fnClickHandler );
				$(els[1]).bind( 'click.DT', { action: "next" }, fnClickHandler );
			},

			"fnUpdate": function ( oSettings, fnDraw ) {
				var iListLength = 5;
				var oPaging = oSettings.oInstance.fnPagingInfo();
				var an = oSettings.aanFeatures.p;
				var i, ien, j, sClass, iStart, iEnd, iHalf=Math.floor(iListLength/2);

				if ( oPaging.iTotalPages < iListLength) {
					iStart = 1;
					iEnd = oPaging.iTotalPages;
				}
				else if ( oPaging.iPage <= iHalf ) {
					iStart = 1;
					iEnd = iListLength;
				} else if ( oPaging.iPage >= (oPaging.iTotalPages-iHalf) ) {
					iStart = oPaging.iTotalPages - iListLength + 1;
					iEnd = oPaging.iTotalPages;
				} else {
					iStart = oPaging.iPage - iHalf + 1;
					iEnd = iStart + iListLength - 1;
				}

				for ( i=0, ien=an.length ; i<ien ; i++ ) {
					// Remove the middle elements
					$('li:gt(0)', an[i]).filter(':not(:last)').remove();

					// Add the new list items and their event handlers
					for ( j=iStart ; j<=iEnd ; j++ ) {
						sClass = (j==oPaging.iPage+1) ? 'class="active"' : '';
						$('<li '+sClass+'><a href="#">'+j+'</a></li>')
							.insertBefore( $('li:last', an[i])[0] )
							.bind('click', function (e) {
								e.preventDefault();
								oSettings._iDisplayStart = (parseInt($('a', this).text(),10)-1) * oPaging.iLength;
								fnDraw( oSettings );
							} );
					}

					// Add / remove disabled classes from the static elements
					if ( oPaging.iPage === 0 ) {
						$('li:first', an[i]).addClass('disabled');
					} else {
						$('li:first', an[i]).removeClass('disabled');
					}

					if ( oPaging.iPage === oPaging.iTotalPages-1 || oPaging.iTotalPages === 0 ) {
						$('li:last', an[i]).addClass('disabled');
					} else {
						$('li:last', an[i]).removeClass('disabled');
					}
				}
			}
		}
	});

	$(document).ready(function() {
    $('table#assets').dataTable( {
        "sDom": "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
        "sPaginationType": "bootstrap"
    });
    
    $('#assets tbody').on('click', 'tr', function() {
    	$('#asset-modal').modal({
    		keyboard: false,
    		remote: $(this).attr('data-href')
    	});
    });

    $('#asset-save').click(function() {
    	$(this).button('loading');
    	$('#asset-modal div.modal-body form').submit();
    });

    $('#asset-delete').click(function() {
    	$(this).button('loading');
    	location.href = $('#asset-modal div.modal-body form').attr('action')+'/delete';
    });

    $('#asset-modal').on('shown', function() {
    	$('.select2').select2();
    });

    $('#asset-modal').on('hidden', function() {
		  $(this).removeData('modal');
		});

		$('#assets_filter label input').focus();
	});

</script>
@stop

@section('styles')
<style>
	table.table thead .sorting,
	table.table thead .sorting_asc,
	table.table thead .sorting_desc,
	table.table thead .sorting_asc_disabled,
	table.table thead .sorting_desc_disabled {
	    cursor: pointer;
	    *cursor: hand;
	}
	 
	table.table thead .sorting { background: url('/packages/datatables/images/sort_both.png') no-repeat center right; }
	table.table thead .sorting_asc { background: url('/packages/datatables/images/sort_asc.png') no-repeat center right; }
	table.table thead .sorting_desc { background: url('/packages/datatables/images/sort_desc.png') no-repeat center right; }
	 
	table.table thead .sorting_asc_disabled { background: url('/packages/datatables/images/sort_asc_disabled.png') no-repeat center right; }
	table.table thead .sorting_desc_disabled { background: url('/packages/datatables/images/sort_desc_disabled.png') no-repeat center right; }
	
	#assets tbody tr:hover { cursor: pointer; }

	div#asset-modal.modal.fade.in { top: 5%; }
	div#asset-modal div.modal-body { max-height: 75%; }


</style>
@stop