<?php
/**
 * Plugin Name: KBoard 전체게시판 통합 모아보기
 * Description: 전체게시판(ID:10)에 모든 게시판의 글을 통합하여 보여주고, 클릭 시 원본 게시판으로 자동 이동
 * Version: 1.0.2
 * Author: Custom
 */

// 보안 체크
if (!defined('ABSPATH')) {
    exit;
}

/* =================================================================
   [KBoard] 전체게시판(ID:10) 통합 모아보기 & 자동 이동 (수정완료)
   ================================================================= */

// 1. 전체게시판(10)에 '나 자신'을 제외한 모든 글 불러오기
add_filter('kboard_list_where', 'my_kboard_all_board_query_clean', 10, 3);
function my_kboard_all_board_query_clean($where, $board_id, $content_list){
    
    $id_all_board = '10'; // 전체게시판 ID

    if($board_id == $id_all_board){
        // 1. 내 게시판(10번) 제외
        // 2. 답글(parent_uid!=0) 제외 -> 원글만 보기
        // 3. 삭제된 글(휴지통) 제외
        // 4. 'notice' 조건 삭제 -> 다른 게시판 공지글도 목록에 나오게 함
        $where = "`board_id` <> '{$id_all_board}' AND `parent_uid`='0' AND (`status`='' OR `status` IS NULL OR `status`='pending_approval')";
    }
    return $where;
}

// 2. 글 클릭 시 '진짜 주인 게시판'으로 이동 (안전한 방법)
add_filter('kboard_url_document_uid', 'my_kboard_all_board_redirect_clean', 10, 3);
function my_kboard_all_board_redirect_clean($url, $content_uid, $board){
    
    $id_all_board = '10';

    if($board->id == $id_all_board){
        $content = new KBContent();
        $content->initWithUID($content_uid);
        $real_board_id = $content->board_id;
        
        // 내 글(10번)이 아니라면, 진짜 주인 게시판으로 보냄
        if($real_board_id && $real_board_id != $id_all_board){
            $real_board = new KBoard($real_board_id);
            $url_generator = new KBUrl();
            $url_generator->setBoard($real_board);
            
            // 방법 1: 메타데이터에 저장된 페이지 ID 사용 (가장 빠르고 안전)
            $page_id = '';
            if($real_board->meta->latest_target_page){
                $page_id = $real_board->meta->latest_target_page;
            }
            else if($real_board->meta->auto_page){
                $page_id = $real_board->meta->auto_page;
            }
            
            // 페이지 ID가 있으면 해당 페이지의 permalink 사용
            if($page_id){
                $page_permalink = get_permalink($page_id);
                if($page_permalink){
                    $url_generator->setPath($page_permalink);
                    $new_url = $url_generator->getDocumentURLWithUID($content_uid);
                    if($new_url) return $new_url;
                }
            }
            
            // 방법 2: 메타데이터에 페이지 ID가 없으면 라우터 리다이렉트 사용
            // getDocumentRedirect()는 라우터가 자동으로 원본 게시판을 찾아줌
            // Elementor 같은 페이지 빌더에서도 작동하고, DB LIKE 쿼리 없이 안전함
            $new_url = $url_generator->getDocumentRedirect($content_uid);
            if($new_url) return $new_url;
        }
    }
    return $url;
}

// 3. 최신순 정렬 (무조건 최신글이 위로)
add_filter('kboard_list_orderby', 'my_kboard_all_board_order_clean', 10, 3);
function my_kboard_all_board_order_clean($orderby, $board_id, $content_list){
    if($board_id == '10'){
        $orderby = "`date` DESC";
    }
    return $orderby;
}

// 4. 닉네임 7자 제한 및 등급 이름 숨기기 (JavaScript)
add_action('wp_footer', 'my_kboard_nickname_limit_script');
function my_kboard_nickname_limit_script(){
    if(!is_admin()){
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // 닉네임 7자 제한 및 등급 이름 숨기기
            function limitNickname() {
                $('.kboard-list-user .kboard-user-display').each(function() {
                    var $display = $(this);
                    var $text = $display.clone();
                    
                    // 등급 아이콘 제외하고 텍스트만 추출
                    $text.find('img').remove();
                    var text = $text.text().trim();
                    
                    // 7자 이상이면 자르기
                    if(text.length > 7) {
                        var limitedText = text.substring(0, 7) + '...';
                        // 기존 텍스트 노드 찾아서 교체
                        $display.contents().filter(function() {
                            return this.nodeType === 3; // 텍스트 노드만
                        }).each(function() {
                            if($(this).text().trim().length > 0) {
                                this.textContent = limitedText;
                            }
                        });
                    }
                });
            }
            
            // 페이지 로드 시 실행
            limitNickname();
            
            // AJAX로 목록이 업데이트될 때도 실행
            $(document).on('kboard_list_updated', function() {
                limitNickname();
            });
        });
        </script>
        <?php
    }
}
