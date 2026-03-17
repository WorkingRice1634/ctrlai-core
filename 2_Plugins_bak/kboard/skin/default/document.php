<?php
// =========================================================================
// ★ [프리미엄 포인트 결제 시스템 - 24시간 만료 & 수익 배분]
// =========================================================================
$premium_cost = 100;    // 차감 포인트
$author_reward = 75;    // 작성자 지급 포인트
$access_duration = 24 * 60 * 60; // 24시간 (초 단위)
$is_unlocked = false;

// 로그인 체크
if($content->option->premium && !is_user_logged_in()){
	echo '<script>alert("로그인이 필요합니다."); location.href="/login";</script>';
	exit;
}

// 1. 관리자이거나, 작성자 본인이거나, 프리미엄 글이 아니면 무조건 통과
if(!$content->option->premium || $content->isEditor() || $board->isAdmin()){
	$is_unlocked = true;
} else {
	$user_id = get_current_user_id();
	
	// 구매 기록 불러오기
	$purchase_history = get_user_meta($user_id, 'ctrlai_premium_purchases', true);
	if(!is_array($purchase_history)) $purchase_history = array();

	// 2. 이미 구매했는지 & 24시간이 안 지났는지 확인
	if(isset($purchase_history[$content->uid])){
		$purchase_time = $purchase_history[$content->uid];
		$time_diff = time() - $purchase_time;

		// 24시간 이내라면 열람 허용
		if($time_diff < $access_duration){
			$is_unlocked = true;
		} else {
			// 시간이 지났으면 기록 삭제 (재결제 유도)
			unset($purchase_history[$content->uid]);
			update_user_meta($user_id, 'ctrlai_premium_purchases', $purchase_history);
		}
	}

	// 3. 결제 진행 로직 (?buy=1 파라미터가 있을 때)
	if(!$is_unlocked && isset($_GET['buy']) && $_GET['buy'] == '1'){
		if(function_exists('mycred_get_users_cred')){
			$current_point = mycred_get_users_cred($user_id);
			
			if($current_point >= $premium_cost){
				
				// [A] 구매자 포인트 차감
				mycred_add('premium_payment', $user_id, -$premium_cost, '프리미엄 열람(24시간): ' . $content->title);
				
				// [B] 작성자 수익 배분 (본인 글 구매 제외)
				if($content->member_uid > 0 && $content->member_uid != $user_id){
					mycred_add('premium_revenue', $content->member_uid, $author_reward, '💰 판매 수익(75P): ' . $content->title);
				}

				// [C] 구매 기록 저장
				$purchase_history[$content->uid] = time();
				update_user_meta($user_id, 'ctrlai_premium_purchases', $purchase_history);
				
				$is_unlocked = true;
				echo '<script>alert("결제가 완료되었습니다. (24시간 동안 열람 가능)");</script>';
			} else {
				echo '<script>alert("포인트가 부족합니다! (필요: '.$premium_cost.'P)"); history.back();</script>';
				exit;
			}
		} else {
			echo '<script>alert("포인트 시스템 오류입니다. 관리자에게 문의하세요."); history.back();</script>';
			exit;
		}
	}
}
?>

<div id="kboard-document">
	<div id="kboard-default-document">
		<div class="kboard-document-wrap" itemscope itemtype="http://schema.org/Article">

			<div class="dc-board-header">
				<div class="dc-board-title">
					<a href="<?php echo esc_url($url->getBoardList()); ?>" style="color: inherit; text-decoration: none;">
						<?php echo esc_html($board->board_name); ?>
					</a>
				</div>
				<?php if(!empty($board->description)): ?>
				<div class="dc-board-desc">
					<?php echo esc_html($board->description); ?>
				</div>
				<?php endif; ?>
			</div>

			<div class="kboard-title" itemprop="name">
				<h1><?php echo $content->title?></h1>
			</div>
			
			<div class="kboard-detail">
				<div class="detail-attr detail-writer">
					<div class="detail-name"><?php echo __('Author', 'kboard')?></div>
					<div class="detail-value"><?php echo $content->getUserDisplay()?></div>
				</div>
				<div class="detail-attr detail-date">
					<div class="detail-name"><?php echo __('Date', 'kboard')?></div>
					<div class="detail-value"><?php echo date('Y-m-d H:i', strtotime($content->date))?></div>
				</div>
				<div class="detail-attr detail-view">
					<div class="detail-name"><?php echo __('Views', 'kboard')?></div>
					<div class="detail-value"><?php echo $content->view?></div>
				</div>
			</div>
			
			<div class="kboard-content" itemprop="description">
				<div class="content-view">
					
					<?php if($is_unlocked): ?>
						
						<?php if($content->option->premium && ($content->isEditor() || $board->isAdmin())): ?>
							<div style="background: #1e2a3e; color: #ffdd57; padding: 10px; border: 1px solid #4a5b75; border-radius: 5px; margin-bottom: 20px; font-size: 13px;">
								💡 작성자 또는 관리자 권한으로 본문을 열람 중입니다.
							</div>
						<?php endif; ?>
						
						<?php if(isset($purchase_history[$content->uid])): ?>
							<div style="background: #0f1520; color: #8894a8; padding: 5px 10px; border-radius: 4px; margin-bottom: 15px; font-size: 12px; text-align: right;">
								⏳ 열람 유효 시간: <?php echo date('Y-m-d H:i', $purchase_history[$content->uid] + $access_duration + (9 * 3600)); ?> 까지
							</div>
						<?php endif; ?>

						<?php echo $content->content?>
						
						<?php
						$prompt_text = $content->option->prompt;
						if($prompt_text):
							$copy_count = intval($content->option->prompt_copy_count);
						?>
							<div class="ctrlai-prompt-container" style="margin-top: 40px; border: 1px solid #3d4a5d; border-radius: 8px; background: #0f1520; overflow: hidden;">
								
								<div style="background: #1e2a3e; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #3d4a5d;">
									<div style="color: #ffdd57; font-weight: bold; font-size: 14px;">✨ 프롬프트</div>
									<div style="display: flex; align-items: center; gap: 10px;">
										
										<?php if($board->isAdmin()): ?>
											<span style="color: #8894a8; font-size: 12px;">(관리자용) 복사 횟수: <span id="prompt_copy_count"><?php echo $copy_count; ?></span>회</span>
										<?php endif; ?>
										
										<button id="ctrlai_copy_btn" data-uid="<?php echo $content->uid; ?>" style="background: #ffdd57; color: #1e2a3e; border: none; padding: 5px 15px; border-radius: 4px; font-weight: bold; font-size: 12px; cursor: pointer;">
											📋 복사하기 (블러 해제)
										</button>
									</div>
								</div>
								
								<div style="padding: 20px; position: relative;">
									<div id="ctrlai_prompt_text" style="color: #d0d7e1; font-family: monospace; white-space: pre-wrap; word-break: break-all; filter: blur(5px); transition: filter 0.3s ease; user-select: none;"><?php echo esc_html($prompt_text); ?></div>
									
									<div id="ctrlai_prompt_overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; background: rgba(15, 21, 32, 0.4); pointer-events: none;">
										<span style="background: rgba(0,0,0,0.8); color: #fff; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight:bold;">상단의 복사하기 버튼을 누르면 공개됩니다.</span>
									</div>
								</div>
							</div>

							<script>
							jQuery(document).ready(function($) {
								$('#ctrlai_copy_btn').on('click', function() {
									var btn = $(this);
									var promptText = $('#ctrlai_prompt_text');
									var overlay = $('#ctrlai_prompt_overlay');
									var rawText = <?php echo json_encode($prompt_text); ?>;
									var uid = btn.data('uid');

									promptText.css({'filter': 'none', 'user-select': 'auto'});
									overlay.fadeOut();

									if (navigator.clipboard && window.isSecureContext) {
										navigator.clipboard.writeText(rawText).then(function() {
											alert('✨ 프롬프트가 클립보드에 복사되었습니다!');
										});
									} else {
										var tempInput = $('<textarea>');
										$('body').append(tempInput);
										tempInput.val(rawText).select();
										document.execCommand('copy');
										tempInput.remove();
										alert('✨ 프롬프트가 클립보드에 복사되었습니다!');
									}
									
									btn.text('✅ 복사 완료').css({'background': '#4a5b75', 'color': '#fff'});

									if(!btn.data('clicked')) {
										btn.data('clicked', true);
										$.ajax({
											url: '<?php echo admin_url("admin-ajax.php"); ?>',
											type: 'POST',
											data: {
												action: 'ctrlai_prompt_copied',
												content_uid: uid
											},
											success: function(res) {
												if(res.success && res.data && res.data.new_count) {
													$('#prompt_copy_count').text(res.data.new_count);
												}
											}
										});
									}
								});
							});
							</script>
						<?php endif; ?>

					<?php else: ?>
						<style>
							#ez-toc-container, .ez-toc-v2, .lwptoc, .rank-math-toc, .toc_container { display: none !important; }
						</style>
						<div style="padding: 100px 20px; text-align: center; background-color: #0f1520; border: 1px solid #4a5b75; border-radius: 8px;">
							<div style="font-size: 60px; margin-bottom: 20px;">👑</div>
							<div style="font-size: 24px; font-weight: bold; color: #ffdd57; margin-bottom: 15px;">프리미엄 콘텐츠</div>
							<p style="color: #ccc; margin-bottom: 30px; line-height:1.6;">이 글은 <strong>100 포인트</strong>가 차감되는 프리미엄 게시물입니다.<br>결제 시점부터 <strong>24시간 동안</strong> 자유롭게 열람 가능합니다.</p>
							
							<div style="display: flex; gap: 10px; justify-content: center;">
								<button onclick="if(confirm('👑 100포인트를 사용하여 24시간 동안 열람하시겠습니까?')){ location.href='<?php echo esc_url($url->getDocumentURLWithUID($content->uid)) . '&buy=1'; ?>'; }" style="padding: 10px 25px; background: #ffdd57; color: #1e2a3e; border: 0; border-radius: 4px; font-weight: bold; cursor: pointer;">결제하고 보기 (100P)</button>
								<button onclick="history.back()" style="padding: 10px 25px; background: #4a5b75; color: #fff; border: 0; border-radius: 4px; cursor: pointer;">돌아가기</button>
							</div>
						</div>
					<?php endif?>

				</div>
			</div>
			
			<div class="kboard-document-action">
				<div class="kboard-vote-action-box">
					<button type="button" class="kboard-vote-btn kboard-vote-like" onclick="kboard_document_like(this)" data-uid="<?php echo $content->uid?>" title="추천">
						<span class="vote-text">👍 추천</span>
						<span class="kboard-document-like-count"><?php echo intval($content->like)?></span>
					</button>
					
					<button type="button" class="kboard-vote-btn kboard-vote-report" onclick="alert('🚨 신고 기능은 준비 중입니다.');" title="신고">
						<span class="vote-text">🚨 신고</span>
					</button>
				</div>
			</div>

			<?php
			$tag_data = $content->option->tags;
			if(!$tag_data) { $tag_data = $content->option->tag; }

			if($tag_data):
				$official_tags = array();
				$terms = get_terms(array('taxonomy' => 'ai_program', 'hide_empty' => false));
				if(!is_wp_error($terms) && !empty($terms)){
					foreach($terms as $term){ $official_tags[] = $term->name; }
				}

				$content_tags = is_array($tag_data) ? $tag_data : explode(',', $tag_data);
				$tag_count = count($content_tags);
				$current_idx = 0;

				echo '<div class="ctrlai-document-tags" style="margin: 20px 0; padding: 15px 0; border-top: 1px solid #1e2a3e; font-size: 14px; color: #fff; display: flex; align-items: center; gap: 5px; flex-wrap: wrap;">';
				echo '<strong style="color: #ffffff; font-style: italic; margin-right: 5px;">tag:</strong>';
				
				foreach($content_tags as $tag):
					$clean_tag = trim($tag);
					if(empty($clean_tag)) continue;
					$current_idx++;
					
					if(in_array($clean_tag, $official_tags)){
						echo '<span style="display:inline-block; background-color:#ffdd57; color:#1e2a3e; padding:3px 8px; border-radius:4px; font-weight:bold; font-size: 12px;">'.esc_html($clean_tag).'</span>';
					} else {
						echo '<span style="display:inline-block; background-color:#2a3546; color:#d0d7e1; padding:3px 8px; border-radius:4px; font-size: 12px;">'.esc_html($clean_tag).'</span>';
					}
					
					if($current_idx < $tag_count) {
						echo '<span style="color: #ffffff; font-weight: bold;">,</span>';
					}
				endforeach;
				echo '</div>';
			endif;
			?>

			<?php if($content->visibleComments()):?>
			<div class="kboard-comments-area"><?php echo $board->buildComment($content->uid)?></div>
			<?php endif?>
			
			<div class="kboard-control">
				<div class="left">
					<a href="<?php echo esc_url($url->getBoardList())?>" class="kboard-default-button-small"><?php echo __('List', 'kboard')?></a>
				</div>
				<?php if($content->isEditor() || $board->permission_write=='all'):?>
				<div class="right">
					<a href="<?php echo esc_url($url->getContentEditor($content->uid))?>" class="kboard-default-button-small"><?php echo __('Edit', 'kboard')?></a>
					<a href="<?php echo esc_url($url->getContentRemove($content->uid))?>" class="kboard-default-button-small" onclick="return confirm('<?php echo __('Are you sure you want to delete?', 'kboard')?>');"><?php echo __('Delete', 'kboard')?></a>
				</div>
				<?php endif?>
			</div>
		</div>
	</div>
</div>