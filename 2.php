<?php

	// Start class nc_ranking2 //

	class nc_ranking2 extends WP_Widget {

		private function from_ranking($category){
			try
			{
				//$cat_url = 'C:\last.jsondeporte';
				$cat_url = 'http://www.theranking.com'.$category.'/last.json';
				$last_jason = file_get_contents($cat_url);
				$last_ranks = json_decode($last_jason, true);
				if (is_array($last_ranks["data"]['subcategories'][0]['popularRankings'])) {
					foreach ($last_ranks["data"]['subcategories'] as $popularRankings ){
					foreach ($popularRankings['popularRankings'] as $popularRanking ) {
							$data_cache['title'] = $popularRanking['data']['title'];
							$data_cache['permalink'] = $popularRanking['data']['permalink'];
							$data_cache['id'] = $popularRanking['data']['id'];
							$data_cache_all[] = $data_cache;
						}
					}
				}
				if (is_array($last_ranks["data"]['lastRankings'])){
					for ($i=1; $i<=2; $i++){
						if ($i==1)$ranks_order = 'mostVotedRankings'; else if ($i==2) $ranks_order = 'lastRankings';
						for($n=0; $n<=2; $n++){
							$data_cache['title'] = $last_ranks["data"][$ranks_order][$n]["data"]['title'];
							$data_cache['permalink'] = $last_ranks["data"][$ranks_order][$n]["data"]['permalink'];
							$data_cache['id'] = $last_ranks["data"][$ranks_order][$n]["data"]['id'];
							$data_cache_all[] = $data_cache;
						}
					}
				}
				return $data_cache_all;
			}
			catch (Exception $e)
			{
				return '';
			}
		}

	// Constructor //

		function nc_ranking2() {
			$widget_ops = array( 'classname' => 'nc_ranking2', 'description' => 'Muestra los rankings desde http://www.theranking.com/' ); // Widget Settings
			$control_ops = array( 'id_base' => 'nc_ranking2' ); // Widget Control Settings
			$this->WP_Widget( 'nc_ranking2', 'ThRanking.com', $widget_ops, $control_ops ); // Create the widget
		}

	// Extract Args //

		function widget($args, $instance) {
			extract( $args );

			$title 		= apply_filters('widget_title', $instance['title']); // the widget title
			$category 	= $instance['categories']; // categories to show from
			$ancho 	= $instance['ancho']; // width
			$alto 	= $instance['alto']; // height


	// Before widget //

			echo $before_widget;

	// Title of widget //

			if ( $title ) { echo $before_title . $title . $after_title; }

	// Widget output //


				$nc_therank_categories = get_option( 'nc_therank_categories' );

			?>

			<p>
			<?php
				$data_cache_name = $this->id.'_cache';
				$data_time_name = $this->id.'_time';
				$data_time = get_option( $data_time_name );
				$curr_time = time();
				if ( $curr_time > $data_time) {
						$data_cache_all = $this->from_ranking($category);
						$data_cache_name = $this->id.'_cache';
						$next_time =  date('U', strtotime('+1 day'));
					update_option( $data_cache_name, $data_cache_all );
					update_option( $data_time_name, $next_time );
				}
				$data_cache_all = get_option( $data_cache_name );

				if (is_array($data_cache_all)){

				$total_arr = count($data_cache_all) - 1;
				$n = rand(0,$total_arr);
				$data_cache = $data_cache_all[$n];
				?>

				<script src="http://www.theranking.com/js/widgets/ranking/embed.js"> </script>
				<script>theranking.widgets.ranking({rankingId : <?php echo $data_cache['id']; ?>, domain : 'http://www.theranking.com', target : 'rankingWidget<?php echo $data_cache['id']; ?>', width : '<?php echo $ancho; ?>', height : '<?php echo $alto; ?>'})</script>
				<div id="rankingWidget<?php echo $data_cache['id']; ?>"> </div>
				<a style="display:block;text-align:center;font-size:9pt;width:<?php echo $ancho; ?>" href="http://www.theranking.com<?php echo $data_cache['permalink']; ?>" title="<?php echo $data_cache['title']; ?>">Ir al ranking</a>

				<?php
				} else echo 'No hay datos disponibles';
			?>
			</p>
			<p>

			</p>

			<?php

	// After widget //

			echo $after_widget;
		}

	// Update Settings //

		function update($new_instance, $old_instance) {
		global $args;
			$instance['title'] = strip_tags($new_instance['title']);
			$instance['categories'] = $new_instance['categories'];
			$instance['ancho'] = $new_instance['ancho'];
			$instance['alto'] = $new_instance['alto'];


		// 	update cached rankins

				$category = $new_instance['categories'];

					$data_cache_all = $this->from_ranking($category);

						$data_cache_name = $this->id.'_cache';

			if ( get_option( $data_cache_name ) ) update_option( $data_cache_name, $data_cache_all ); else add_option( $data_cache_name, $data_cache_all, ' ', 'no' );
			update_option( $data_cache_name, $data_cache_all );
					$next_time = date('U', strtotime('+1 day'));
					$data_time_name = $this->id.'_time';
					update_option( $data_time_name, $next_time );
			return $instance;
		}

	// Widget Control Panel //

		function form($instance) {
		global $id;

		if ( get_option( 'nc_therank_categories' ) ) {
				$nc_therank_categories = get_option( 'nc_therank_categories' );
			} else {
			$therankcategories = file_get_contents("http://www.theranking.com/es/categories.json");

			$lastjason = json_decode($therankcategories, true);

			$ca = 0;
			foreach ($lastjason["data"]['categories'] as $categories){

				$title = $categories["data"]["lang"]["es"]['title'];
				$permalink = $categories["data"]["permalink"]["es"];
				$therank_categories[$ca]['title'] = $title;
				$therank_categories[$ca]['permalink'] = $permalink;
				if( is_array($categories["data"]["sons"])) {
				$so = 0;
					foreach ($categories["data"]["sons"] as $son) {
						$son_title = $son["data"]["lang"]["es"]['title'];
						$son_permalink = $son["data"]["permalink"]["es"];

						$sons[$so]['title'] = $son_title;
						$sons[$so]['permalink'] = $son_permalink;
						$so = $so +1;
					}
					$therank_categories[$ca]['sons'] = $sons;
					unset($sons);
				}
				$ca = $ca +1;
			}

			add_option( 'nc_therank_categories', $therank_categories, ' ', 'no' );
		}
		$defaults = array( 'title' => 'The Ranking', 'ancho' => '350px', 'alto' => '500px', 'categories' => 'deportes' );
		$instance = wp_parse_args( (array) $instance, $defaults );

		?>

		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">T&iacute;tulo:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>'" type="text" value="<?php echo $instance['title']; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('categories'); ?>">Seleccione una categoria:</label>
			<select id="<?php echo $this->get_field_id('categories'); ?>" name="<?php echo $this->get_field_name('categories'); ?>" style="width:100%;">
				<?php $nc_therank_categories = get_option( 'nc_therank_categories' );
					foreach ($nc_therank_categories as $nc_therank_categorie){
							echo '<option value="'.$nc_therank_categorie["permalink"].'" ';
							 if ($nc_therank_categorie["permalink"] == $instance['categories']) echo 'selected="selected" ';
							 echo '>'.$nc_therank_categorie["title"].'</option>';
						if ( is_array($nc_therank_categorie["sons"])){
							foreach ($nc_therank_categorie["sons"] as $nc_therank_son){
							echo '<option value="'.$nc_therank_son["permalink"].'" ';
							 if ($nc_therank_son["permalink"] == $instance['categories']) echo 'selected="selected" ';
							 echo '> &nbsp;&nbsp;'.$nc_therank_son["title"].'</option>';
						}

					}
					}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('ancho'); ?>">Ancho:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('ancho'); ?>" name="<?php echo $this->get_field_name('ancho'); ?>'" type="text" value="<?php echo $instance['ancho']; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('alto'); ?>">Alto:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('alto'); ?>" name="<?php echo $this->get_field_name('alto'); ?>'" type="text" value="<?php echo $instance['alto']; ?>" />
		</p>

        <?php }

}

// End class nc_ranking2

add_action('widgets_init', create_function('', 'return register_widget("nc_ranking2");'));


?>