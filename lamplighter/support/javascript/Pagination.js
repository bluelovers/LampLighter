
	function Pagination() {
		
		//
		// Properties
		//
		
		this.reference_var_name;
		
		// Number of pages, etc
		this.num_items_total 	  = 0;
		this.current_page    	  = 0;
		this.max_pages			  = 0;
		
		// Link/Text setup

		this.link_base 		 	  = '?';
		this.link_class			  = 'page_link';
		this.link_onclick		  = '';
		this.link_href			  = null;
		this.page_links_object_id = 'page_links';
		this.active_page_class_name = '';
		this.active_page_style 	  = 'font-weight:bold;';		
		this.text_class			  = '';
		this.links_per_offset	  = 10;
		this.link_bookend_l		  = '[ ';
		this.link_bookend_r		  = ' ]';
		this.bookend_class		  = '';
		this.offset_link_class	  = 'page_link';
		this.offset_link_style    = 'cursor:pointer;';
		this.next_offset_caption  = 'Next &gt;&gt;';
		this.prev_offset_caption  = '&lt;&lt; Back';
		this.page_links_caption   = 'Page: ';
		this.pages_linked_through_uri = true;
		this.query_string		  = '';

		// Options
		this.qs_key_page		 = 'page';
		this.hide_if_no_items 	 = true; 
		this.unlink_current_page = true;
		this.single_page_caption = 'This is the only page of results';
		this.show_script_errors  = false;

		//
		// Callbacks
		//
		this.before_generate = null;
		this.after_generate = null; 

		//
		// Private properties
		//
		this._Current_offset = 1;

		//
		// Methods
		//
		
		this.generate_pagination = generate_pagination;

		function generate_pagination( requested_offset ) {
			
			try { 

				var j;
				var num_pages   = 0;
				var html        = '';

				var next_offset;
				var prev_offset;
				var offset_page_start = 0;
				var qs_delimiter;
				
				var continue_div_search = true;
				var count = 0;
	
				var callback_options = {
					'requested_offset': requested_offset,
					'num_items_total': this.num_items_total
				};
	
				if ( typeof(this.before_generate) == 'function' ) {
					if ( !this.before_generate(callback_options) ) {
						return false;
					}
				}
	
				if ( this.num_items_total > 0 ) {

					num_pages = ( this.items_per_page > 0 ) ? Math.ceil ( this.num_items_total / this.items_per_page ) : 1;
					num_pages = ( this.max_pages && num_pages > this.max_pages ) ? this.max_pages : num_pages;

					if ( num_pages <= 1 && this.single_page_caption ) {
						html = '<span class="' + this.text_class + '">' + this.single_page_caption + '</span>' + "\n";
					}
					else {
						
						//
						// Page is greater than 1
						//
						
						if ( isNaN(requested_offset) || requested_offset < 1 ) {
					
							if ( this.current_page > this.links_per_offset ) {	
								this._Current_offset = Math.ceil(this.current_page / this.links_per_offset);
							}
							else {
								this._Current_offset = 1;	
							}
						}
						else {
							this._Current_offset = requested_offset;
						}

						next_offset = this._Current_offset + 1;
						prev_offset = ( this._Current_offset > 1 ) ? this._Current_offset - 1 : 1;	

						offset_page_start = ( this._Current_offset <= 1 ) ? 1 : ((this._Current_offset - 1) * this.links_per_offset) + 1;

						if ( offset_page_start > num_pages ) {
							offset_page_start = math.ceil( num_pages / this.links_per_offset );
						}

						if ( offset_page_start > this.links_per_offset ) {

							//
							// We're on a page higher than 1
							//

							html = html + '<a class="' + this.offset_link_class + '" href="#" onclick="' + this.reference_var_name + '.generate_pagination(' + prev_offset + ')">' + this.prev_offset_caption + '</a> ';
						}
						else {
							if ( this.page_links_caption ) {
								html = html + '<span class="' + this.text_class + '">' + this.page_links_caption + '</span>';
							}

						} 
			
						if ( this.link_bookend_l ) { 
							html = html + '<span class="' + this.bookend_class + '">' + this.link_bookend_l + '</span>';
						}

						
						for ( j = offset_page_start; j < (offset_page_start + this.links_per_offset) && j <= num_pages; j++ ) {
							if ( j == this.current_page ) {
								html = html + '<span style="' + this.active_page_style + '"';

								if ( this.active_page_class != '' ) {
									html = html + ' class="' + this.active_page_class + '"';
								}
								else {
									html = html + ' class="' + this.text_class + '"';
								}
						
								html = html + '>';
								html = html + String(j) + ' ';
								html = html + '</span>';
							}
							else {
								html = html + '<a '; 
								html = html + ' onclick="' + this.link_onclick + '"';
								html = html + ' class="' + this.link_class + '"';
								
								if ( this.link_href === null ) {
									html = html + ' href="' + this.link_base;
									
									if ( this.pages_linked_through_uri ) {
										
										var link_base_trailer = this.link_base.substr(this.link_base.length-1, 1);
										
										if ( link_base_trailer != '/' && link_base_trailer != '#') {
											html = html + '/';
										}
										
										html = html + String(j);
									}
									else {
										if ( this.link_base == '' ) {
											this.link_base = '?';
											qs_delimiter = '';
										}
										else {
											qs_delimiter = ( this.link_base.indexOf('?') == -1 ) ? '?' : '&';
										}
										
										html = html + qs_delimiter + this.qs_key_page + '=' + String(j);
									}

									//
									// Yes, find qs_delimiter again. 
									//
									qs_delimiter = ( this.pages_linked_through_uri && (this.link_base.indexOf('?') == -1) ) ? '?' : '&';
								
									if ( this.query_string ) {
										html = html + qs_delimiter + this.query_string;
									}

									html = html + '"';
								}
								else {
									html = html + ' href="' + this.link_href + '"';
								}
								
								html = html + '>' + String(j) + '</a> ';
							}
						}

						if ( this.link_bookend_r ) { 
							html = html +  '<span class="' + this.bookend_class + '">' + this.link_bookend_r + '</span>';
						}

						if ( (offset_page_start + (this.links_per_offset -1)) < num_pages ) {
							html = html + ' <a class="' + this.offset_link_class + '" href="#" onclick="' + this.reference_var_name + '.generate_pagination(' + next_offset + ')">' + this.next_offset_caption + '</a>';
						}
					}
				}
				else {
					html = html + '';
				}	
				
				if ( page_div = document.getElementById(this.page_links_object_id) ) {
					page_div.innerHTML = html;
				}

				while ( continue_div_search && count < Pagination.MAX_DIVS ) {
			
					if ( page_div = document.getElementById(this.page_links_object_id + String(count+1)) ) {
						page_div.innerHTML = html;
					}
					else {
						continue_div_search = false;
					}

					count = count + 1;
					
				}
				
				if ( typeof(this.after_generate) == 'function' ) {
					if ( !this.after_generate(callback_options) ) {
						return false;
					}
				}
				
		
			} 
			catch( e ) {
				if ( this.DEBUG || this.show_script_errors ) {
					alert (e);
				}
			}		
		
		}
	}

	Pagination.MAX_DIVS = 10;
	Pagination.DEBUG    = false;
