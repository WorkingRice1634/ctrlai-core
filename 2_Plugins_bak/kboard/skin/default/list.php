<div id="kboard-default-list">

	<div class="arca-header-wrapper">
		<div class="arca-board-info">
			<div class="arca-title-row">
				<h2><a href="<?php echo $url->set('mod', 'list')->toString()?>"><?php echo $board->board_name ?></a></h2>
			</div>
			<div class="arca-desc-row">
				<?php echo (isset($board->meta->description) && $board->meta->description) ? $board->meta->description : '게시판 설명이 없습니다.'; ?>
			</div>
		</div>

		<div class="arca-toolbar">
			<div class="arca-left-tools">
				
				<?php 
					$current_sort = isset($_GET['kboard_list_sort']) ? $_GET['kboard_list_sort'] : 'newest';
					$current_cut = isset($_GET['kboard_vote_cut']) ? $_GET['kboard_vote_cut'] : ''; 
				?>

				<a href="<?php echo $url->set('mod', 'list')->set('kboard_list_sort', '')->set('target', '')->set('keyword', '')->set('kboard_vote_cut', '')->toString()?>" class="arca-btn arca-btn-all <?php if(!$current_sort || $current_sort == 'newest') echo 'active'; ?>">
					<i class="fas fa-bars"></i> 전체글
				</a>
				
				<a href="<?php echo $url->set('mod', 'list')->set('kboard_list_sort', 'best')->set('kboard_vote_cut', '')->toString()?>" class="arca-btn arca-btn-concept <?php if($current_sort == 'best') echo 'active'; ?>">
					<i class="fas fa-star"></i> 개념글
				</a>

				<div class="arca-select-wrap">
					<select name="kboard_list_sort" onchange="window.location.href=this.value">
						<option value="<?php echo $url->set('kboard_list_sort', 'newest')->toString()?>"<?php if($current_sort == 'newest'):?> selected<?php endif?>>등록순</option>
						<option value="<?php echo $url->set('kboard_list_sort', 'best')->toString()?>"<?php if($current_sort == 'best'):?> selected<?php endif?>>추천순</option>
						<option value="<?php echo $url->set('kboard_list_sort', 'updated')->toString()?>"<?php if($current_sort == 'updated'):?> selected<?php endif?>>업데이트순</option>
						<option value="<?php echo $url->set('kboard_list_sort', 'view')->toString()?>"<?php if($current_sort == 'viewed'):?> selected<?php endif?>>조회순</option>
					</select>
				</div>

				<div class="arca-select-wrap">
					<select name="kboard_vote_cut" onchange="window.location.href=this.value">
						<option value="<?php echo $url->set('kboard_vote_cut', '')->toString() ?>" <?php if($current_cut == '') echo 'selected'; ?>>추천컷</option>
						<option value="<?php echo $url->set('kboard_vote_cut', '5')->toString() ?>" <?php if($current_cut == '5') echo 'selected'; ?>>추천 5</option>
						<option value="<?php echo $url->set('kboard_vote_cut', '10')->toString() ?>" <?php if($current_cut == '10') echo 'selected'; ?>>추천 10</option>
						<option value="<?php echo $url->set('kboard_vote_cut', '20')->toString() ?>" <?php if($current_cut == '20') echo 'selected'; ?>>추천 20</option>
						<option value="<?php echo $url->set('kboard_vote_cut', '30')->toString() ?>" <?php if($current_cut == '30') echo 'selected'; ?>>추천 30</option>
						<option value="<?php echo $url->set('kboard_vote_cut', '50')->toString() ?>" <?php if($current_cut == '50') echo 'selected'; ?>>추천 50</option>
						<option value="<?php echo $url->set('kboard_vote_cut', '70')->toString() ?>" <?php if($current_cut == '70') echo 'selected'; ?>>추천 70</option>
						<option value="<?php echo $url->set('kboard_vote_cut', '100')->toString() ?>" <?php if($current_cut == '100') echo 'selected'; ?>>추천 100</option>
					</select>
				</div>
			</div>

			<div class="arca-right-tools">
				<?php if($board->isWriter()):?>
				<a href="<?php echo $url->getContentEditor()?>" class="arca-btn-write">
					<i class="fas fa-pen"></i> 글쓰기
				</a>
				<?php endif?>
			</div>
		</div>

		<div class="arca-ad-banner">
			<span>광고 배너 영역 (970x90 권장)</span>
		</div>
	</div>

	<?php
	global $wpdb;
	$board_id = $board->id;

	// 이 게시판에 등록된 글들의 태그 데이터를 DB에서 긁어오기
	$query = $wpdb->prepare("
		SELECT o.option_value
		FROM {$wpdb->prefix}kboard_board_option AS o
		INNER JOIN {$wpdb->prefix}kboard_board_content AS c ON o.content_uid = c.uid
		WHERE c.board_id = %d AND o.option_key IN ('tags', 'tag') AND c.status = ''
	", $board_id);
	
	$tag_results = $wpdb->get_col($query);
	$tag_counts = array();

	// 가져온 태그들을 분리하고 갯수 세기
	if(!empty($tag_results)){
		foreach($tag_results as $val) {
			if(empty($val)) continue;
			$tags = explode(',', $val);
			foreach($tags as $t) {
				$t = trim($t);
				if(empty($t)) continue;
				if(isset($tag_counts[$t])) {
					$tag_counts[$t]++;
				} else {
					$tag_counts[$t] = 1;
				}
			}
		}
	}

	// 많이 쓰인 순으로 정렬 후 상위 15개만 추출
	arsort($tag_counts);
	$popular_tags = array_slice(array_keys($tag_counts), 0, 15);

	// 태그가 하나라도 있을 때만 영역 표시
	if(!empty($popular_tags)):
	?>
	<style>
		.ctrlai-tags-scroll-wrap { margin: 15px 0 25px 0; padding: 15px; background: #0f1520; border: 1px solid #1e2a3e; border-radius: 8px; }
		.ctrlai-tags-title { color: #ffdd57; font-weight: bold; margin-bottom: 12px; font-size: 14px; display: flex; align-items: center; gap: 5px; }
		
		/* 가로 스크롤 설정 */
		.ctrlai-tags-scroll { 
			display: flex; overflow-x: auto; gap: 8px; padding-bottom: 10px; white-space: nowrap; 
		}
		
		/* 얇고 예쁜 스크롤바 디자인 추가 */
		.ctrlai-tags-scroll::-webkit-scrollbar { height: 6px; }
		.ctrlai-tags-scroll::-webkit-scrollbar-track { background: #172130; border-radius: 4px; }
		.ctrlai-tags-scroll::-webkit-scrollbar-thumb { background: #3d4a5d; border-radius: 4px; }
		.ctrlai-tags-scroll::-webkit-scrollbar-thumb:hover { background: #ffdd57; }
		
		/* 태그 뱃지 스타일 (찌그러짐 방지 적용) */
		.ctrlai-pop-tag {
			flex-shrink: 0;
			background: #1e2a3e; color: #d0d7e1; padding: 6px 15px; border-radius: 20px; font-size: 13px; font-weight: bold; border: 1px solid #3d4a5d; transition: all 0.2s ease; cursor: pointer; display: inline-block; text-decoration: none;
		}
		.ctrlai-pop-tag:hover { background: #ffdd57; color: #1e2a3e; border-color: #ffdd57; }
	</style>

	<div class="ctrlai-tags-scroll-wrap">
		<div class="ctrlai-tags-title">🔥 이 게시판의 인기 태그</div>
		<div class="ctrlai-tags-scroll">
			<?php foreach($popular_tags as $tag_name): ?>
				<a href="<?php echo $url->set('keyword', $tag_name)->set('target', '')->set('mod', 'list')->toString(); ?>" class="ctrlai-pop-tag">#<?php echo esc_html($tag_name); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>
	<?php
	$official_tags = array();
	$terms = get_terms(array('taxonomy' => 'ai_program', 'hide_empty' => false));
	if(!is_wp_error($terms) && !empty($terms)){
		foreach($terms as $term){ $official_tags[] = $term->name; }
	}
	?>

	<div class="kboard-list">
		<table>
			<thead>
				<tr>
					<td class="kboard-list-uid"><?php echo __('Number', 'kboard')?></td>
					<td class="kboard-list-title"><?php echo __('Title', 'kboard')?></td>
					
					<?php if($board->id == '10'):?>
					<td class="kboard-list-category1">게시판</td>
					<?php endif?>
					
					<td class="kboard-list-user"><?php echo __('Author', 'kboard')?></td>
					<td class="kboard-list-date"><?php echo __('Date', 'kboard')?></td>
					<td class="kboard-list-vote"><?php echo __('Votes', 'kboard')?></td>
					<td class="kboard-list-view"><?php echo __('Views', 'kboard')?></td>
				</tr>
			</thead>
			<tbody>
				
				<?php while($content = $list->hasNextNotice()):?>
				<tr class="<?php echo esc_attr($content->getClass())?>">
					<td class="kboard-list-uid"><?php echo __('Notice', 'kboard')?></td>
					<td class="kboard-list-title">
						<?php 
						// ★ 24시간 만료 체크 로직 추가 ★
						$is_premium = $content->option->premium; 
						$is_paid_valid = false;
						if($is_premium && is_user_logged_in()){
							$user_id = get_current_user_id();
							$purchase_history = get_user_meta($user_id, 'ctrlai_premium_purchases', true);
							if(!is_array($purchase_history)) $purchase_history = array();
							
							if(isset($purchase_history[$content->uid])){
								$purchase_time = $purchase_history[$content->uid];
								// 86400초(24시간) 이내라면 유효
								if((time() - $purchase_time) < (24 * 60 * 60)){
									$is_paid_valid = true;
								}
							}
						}
						?>
						<a href="<?php echo esc_url($url->getDocumentURLWithUID($content->uid))?>"
						   <?php if($is_premium && !$content->isEditor() && !$board->isAdmin() && !$is_paid_valid):?>
						   onclick="if(!confirm('👑 100포인트가 필요한 글입니다.\n포인트를 사용하여 열람하시겠습니까?')){return false;} else { this.href += '&buy=1'; }"
						   <?php endif?>
						   style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; width: 100%; text-decoration: none; row-gap: 6px;"
						>
							
							<div style="flex: 1 1 0%; text-align: left; margin-right: 10px; min-width: 150px; word-break: break-word; line-height: 1.5;">
								<?php if($content->isNew()):?><span class="kboard-default-new-notify">New</span><?php endif?>
								<?php if($content->secret):?><img src="<?php echo $skin_path?>/images/icon-lock.png" alt="<?php echo __('Secret', 'kboard')?>"><?php endif?>
								
								<?php echo $content->title?>

								<?php if($is_premium):?>
									<span style="color: #ffdd57; font-weight: bold; margin-left: 5px; background: #333; padding: 2px 6px; border-radius: 4px; font-size: 11px; vertical-align: middle;">👑 Premium</span>
								<?php endif?>
								
								<span class="kboard-comments-count"><?php echo $content->getCommentsCount()?></span>
							</div>

							<div class="ctrlai-list-tags" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 4px; flex-shrink: 0;">
								<?php 
								$tag_data = $content->option->tags;
								if(!$tag_data) { $tag_data = $content->option->tag; }

								$content_tags = array();
								if(is_array($tag_data)) {
									$content_tags = $tag_data;
								} elseif(is_string($tag_data) && !empty($tag_data)) {
									$content_tags = explode(',', $tag_data);
								}

								if(!empty($content_tags)):
									$tag_count = count($content_tags);
									$current_idx = 0;
									foreach($content_tags as $tag):
										$clean_tag = trim($tag);
										if(empty($clean_tag)) continue;
										$current_idx++;
										
										if(in_array($clean_tag, $official_tags)):
											echo '<span style="display:inline-block; background-color:#ffdd57; color:#1e2a3e; padding:1px 6px; border-radius:4px; font-size:11px; font-weight:bold;">'.esc_html($clean_tag).'</span>';
										else:
											echo '<span style="display:inline-block; background-color:#2a3546; color:#d0d7e1; padding:1px 6px; border-radius:4px; font-size:11px;">'.esc_html($clean_tag).'</span>';
										endif;

										if($current_idx < $tag_count) {
											echo '<span style="color: #ffffff; font-size: 11px; font-weight: bold;">,</span>';
										}
									endforeach;
								endif;
								?>
							</div>
						</a>
					</td>
					<?php if($board->id == '10'):?>
					<td class="kboard-list-category1">
						<?php if($content->board_id){ $t = new KBoard($content->board_id); echo $t->board_name; } ?>
					</td>
					<?php endif?>
					<td class="kboard-list-user"><?php echo $content->getUserDisplay()?></td>
					<td class="kboard-list-date"><?php echo $content->getDate()?></td>
					<td class="kboard-list-vote"><?php echo $content->vote?></td>
					<td class="kboard-list-view"><?php echo $content->view?></td>
				</tr>
				<?php endwhile?>

				<?php while($content = $list->hasNext()):?>
				<tr class="<?php echo esc_attr($content->getClass())?>">
					<td class="kboard-list-uid"><?php echo $list->index()?></td>
					<td class="kboard-list-title">
						<?php 
						// ★ 24시간 만료 체크 로직 추가 (일반글 루프) ★
						$is_premium = $content->option->premium; 
						$is_paid_valid = false;
						if($is_premium && is_user_logged_in()){
							$user_id = get_current_user_id();
							$purchase_history = get_user_meta($user_id, 'ctrlai_premium_purchases', true);
							if(!is_array($purchase_history)) $purchase_history = array();
							
							if(isset($purchase_history[$content->uid])){
								$purchase_time = $purchase_history[$content->uid];
								// 86400초(24시간) 이내라면 유효
								if((time() - $purchase_time) < (24 * 60 * 60)){
									$is_paid_valid = true;
								}
							}
						}
						?>
						<a href="<?php echo esc_url($url->getDocumentURLWithUID($content->uid))?>"
						   <?php if($is_premium && !$content->isEditor() && !$board->isAdmin() && !$is_paid_valid):?>
						   onclick="if(!confirm('👑 100포인트가 필요한 글입니다.\n포인트를 사용하여 열람하시겠습니까?')){return false;} else { this.href += '&buy=1'; }"
						   <?php endif?>
						   style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; width: 100%; text-decoration: none; row-gap: 6px;"
						>
							<div style="flex: 1 1 0%; text-align: left; margin-right: 10px; min-width: 150px; word-break: break-word; line-height: 1.5;">
								<?php if($content->isNew()):?><span class="kboard-default-new-notify">New</span><?php endif?>
								<?php if($content->secret):?><img src="<?php echo $skin_path?>/images/icon-lock.png" alt="<?php echo __('Secret', 'kboard')?>"><?php endif?>
								
								<?php echo $content->title?>
								
								<?php if($is_premium):?>
									<span style="color: #ffdd57; font-weight: bold; margin-left: 5px; background: #333; padding: 2px 6px; border-radius: 4px; font-size: 11px; vertical-align: middle;">👑 Premium</span>
								<?php endif?>
								
								<span class="kboard-comments-count"><?php echo $content->getCommentsCount()?></span>
							</div>

							<div class="ctrlai-list-tags" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 4px; flex-shrink: 0;">
								<?php 
								$tag_data = $content->option->tags;
								if(!$tag_data) { $tag_data = $content->option->tag; }

								$content_tags = array();
								if(is_array($tag_data)) {
									$content_tags = $tag_data;
								} elseif(is_string($tag_data) && !empty($tag_data)) {
									$content_tags = explode(',', $tag_data);
								}

								if(!empty($content_tags)):
									$tag_count = count($content_tags);
									$current_idx = 0;
									foreach($content_tags as $tag):
										$clean_tag = trim($tag);
										if(empty($clean_tag)) continue;
										$current_idx++;
										
										if(in_array($clean_tag, $official_tags)):
											echo '<span style="display:inline-block; background-color:#ffdd57; color:#1e2a3e; padding:1px 6px; border-radius:4px; font-size:11px; font-weight:bold;">'.esc_html($clean_tag).'</span>';
										else:
											echo '<span style="display:inline-block; background-color:#2a3546; color:#d0d7e1; padding:1px 6px; border-radius:4px; font-size:11px;">'.esc_html($clean_tag).'</span>';
										endif;

										if($current_idx < $tag_count) {
											echo '<span style="color: #ffffff; font-size: 11px; font-weight: bold;">,</span>';
										}
									endforeach;
								endif;
								?>
							</div>
						</a>
						
						<div class="kboard-mobile-contents">
							<span class="contents-item kboard-user"><?php echo $content->getUserDisplay()?></span>
							<span class="contents-separator kboard-date">|</span>
							<span class="contents-item kboard-date"><?php echo $content->getDate()?></span>
							<span class="contents-separator kboard-vote">|</span>
							<span class="contents-item kboard-vote"><?php echo __('Votes', 'kboard')?> <?php echo $content->vote?></span>
							<span class="contents-separator kboard-view">|</span>
							<span class="contents-item kboard-view"><?php echo __('Views', 'kboard')?> <?php echo $content->view?></span>
						</div>
					</td>
					<?php if($board->id == '10'):?>
					<td class="kboard-list-category1">
						<?php if($content->board_id){ $t = new KBoard($content->board_id); echo $t->board_name; } ?>
					</td>
					<?php endif?>
					<td class="kboard-list-user"><?php echo $content->getUserDisplay()?></td>
					<td class="kboard-list-date"><?php echo $content->getDate()?></td>
					<td class="kboard-list-vote"><?php echo $content->vote?></td>
					<td class="kboard-list-view"><?php echo $content->view?></td>
				</tr>
				<?php endwhile?>
			</tbody>
		</table>
	</div>
	
	<div class="kboard-pagination">
		<ul class="kboard-pagination-pages">
			<?php echo kboard_pagination($list->page, $list->total, $list->rpp)?>
		</ul>
	</div>
	<div class="kboard-search">
		<form id="kboard-search-form-<?php echo $board->id?>" method="get" action="<?php echo esc_url($url->toString())?>">
			<?php echo $url->set('pageid', '1')->set('target', '')->set('keyword', '')->set('mod', 'list')->toInput()?>
			<select name="target">
				<option value=""><?php echo __('All', 'kboard')?></option>
				<option value="title"<?php if(kboard_target() == 'title'):?> selected<?php endif?>><?php echo __('Title', 'kboard')?></option>
				<option value="content"<?php if(kboard_target() == 'content'):?> selected<?php endif?>><?php echo __('Content', 'kboard')?></option>
				<option value="member_display"<?php if(kboard_target() == 'member_display'):?> selected<?php endif?>><?php echo __('Author', 'kboard')?></option>
			</select>
			<input type="text" name="keyword" value="<?php echo esc_attr(kboard_keyword())?>">
			<button type="submit" class="kboard-default-button-small"><?php echo __('Search', 'kboard')?></button>
		</form>
	</div>
	<?php if($board->contribution()):?>
	<div class="kboard-default-poweredby">
		<a href="https://www.cosmosfarm.com/products/kboard" onclick="window.open(this.href);return false;" title="<?php echo __('KBoard is the best community software available for WordPress', 'kboard')?>">Powered by KBoard</a>
	</div>
	<?php endif?>

</div>