<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import { MessageCircle } from 'lucide-vue-next';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import type { Comment } from '@/types';

const props = defineProps<{
    comments: Comment[];
    storyId: number;
    isAdmin: boolean;
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const totalCommentCount = computed(() =>
    props.comments.reduce((count, comment) => count + 1 + (comment.replies?.length ?? 0), 0),
);

const newBody = ref('');
const isSubmitting = ref(false);

const editingId = ref<number | null>(null);
const editBody = ref('');

const confirmDeleteId = ref<number | null>(null);

const replyingToId = ref<number | null>(null);
const replyBody = ref('');
const isSubmittingReply = ref(false);

function submitComment(): void {
    if (!newBody.value.trim() || isSubmitting.value) {
        return;
    }
    isSubmitting.value = true;
    router.post(`/stories/${props.storyId}/comments`, {
        body: newBody.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newBody.value = '';
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
}

function startReply(commentId: number): void {
    replyingToId.value = commentId;
    replyBody.value = '';
    editingId.value = null;
    confirmDeleteId.value = null;
}

function cancelReply(): void {
    replyingToId.value = null;
    replyBody.value = '';
}

function submitReply(parentId: number): void {
    if (!replyBody.value.trim() || isSubmittingReply.value) {
        return;
    }
    isSubmittingReply.value = true;
    router.post(`/stories/${props.storyId}/comments`, {
        body: replyBody.value,
        parent_id: parentId,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            replyingToId.value = null;
            replyBody.value = '';
        },
        onFinish: () => {
            isSubmittingReply.value = false;
        },
    });
}

function startEdit(comment: Comment): void {
    editingId.value = comment.id;
    editBody.value = comment.body;
    confirmDeleteId.value = null;
    replyingToId.value = null;
}

function cancelEdit(): void {
    editingId.value = null;
    editBody.value = '';
}

function saveEdit(comment: Comment): void {
    if (!editBody.value.trim()) {
        return;
    }
    router.put(`/stories/${props.storyId}/comments/${comment.id}`, {
        body: editBody.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            editBody.value = '';
        },
    });
}

function toggleDelete(commentId: number): void {
    confirmDeleteId.value = confirmDeleteId.value === commentId ? null : commentId;
}

function deleteComment(comment: Comment): void {
    router.delete(`/stories/${props.storyId}/comments/${comment.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            confirmDeleteId.value = null;
        },
    });
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .map(w => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

function relativeTime(dateString: string): string {
    const now = new Date();
    const date = new Date(dateString);
    const diffMs = now.getTime() - date.getTime();
    const diffSeconds = Math.floor(diffMs / 1000);
    const diffMinutes = Math.floor(diffSeconds / 60);
    const diffHours = Math.floor(diffMinutes / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffSeconds < 60) {
        return 'baru saja';
    }
    if (diffMinutes < 60) {
        return `${diffMinutes}m lalu`;
    }
    if (diffHours < 24) {
        return `${diffHours}j lalu`;
    }
    return `${diffDays}h lalu`;
}
</script>

<template>
    <div class="px-4 py-6">
        <!-- Header -->
        <div class="flex items-center gap-2 mb-3">
            <MessageCircle class="size-4 text-muted-foreground" />
            <span class="font-medium text-sm">Komentar</span>
            <span
                v-if="totalCommentCount > 0"
                class="bg-muted px-1.5 py-0.5 rounded-full text-muted-foreground text-xs"
            >
                {{ totalCommentCount }}
            </span>
        </div>

        <Separator class="mb-4" />

        <!-- Comment Form (authenticated) -->
        <div v-if="currentUser" class="flex gap-3 mb-6">
            <div class="flex-shrink-0 flex items-center justify-center bg-muted rounded-full size-8 font-medium text-xs text-muted-foreground">
                {{ getInitials(currentUser.name) }}
            </div>
            <div class="flex-1 space-y-2">
                <Textarea
                    v-model="newBody"
                    placeholder="Tulis komentar..."
                    class="min-h-[72px] resize-none text-sm"
                    @keydown.meta.enter="submitComment"
                    @keydown.ctrl.enter="submitComment"
                />
                <div class="flex justify-end">
                    <button
                        class="bg-primary hover:bg-primary/90 px-4 py-1.5 rounded-md font-medium text-primary-foreground text-sm transition-colors disabled:opacity-50"
                        :disabled="!newBody.trim() || isSubmitting"
                        @click="submitComment"
                    >
                        Kirim
                    </button>
                </div>
            </div>
        </div>

        <!-- Guest prompt -->
        <div v-else class="mb-6 text-center">
            <Link
                href="/login"
                class="text-sm text-muted-foreground hover:text-foreground transition-colors underline underline-offset-4"
            >
                Masuk untuk berkomentar
            </Link>
        </div>

        <!-- Comment List -->
        <div v-if="comments.length > 0" class="space-y-4">
            <div
                v-for="comment in comments"
                :key="comment.id"
            >
                <!-- Top-level comment -->
                <div class="flex gap-3">
                    <div class="flex-shrink-0 flex items-center justify-center bg-muted rounded-full size-8 font-medium text-xs text-muted-foreground">
                        {{ getInitials(comment.user.name) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-sm">{{ comment.user.name }}</span>
                            <span class="text-muted-foreground text-xs">{{ relativeTime(comment.created_at) }}</span>
                        </div>

                        <!-- Editing mode -->
                        <div v-if="editingId === comment.id" class="mt-1 space-y-2">
                            <Textarea
                                v-model="editBody"
                                class="min-h-[60px] resize-none text-sm"
                                @keydown.meta.enter="saveEdit(comment)"
                                @keydown.ctrl.enter="saveEdit(comment)"
                            />
                            <div class="flex gap-2">
                                <button
                                    class="bg-primary hover:bg-primary/90 px-3 py-1 rounded-md font-medium text-primary-foreground text-xs transition-colors disabled:opacity-50"
                                    :disabled="!editBody.trim()"
                                    @click="saveEdit(comment)"
                                >
                                    Simpan
                                </button>
                                <button
                                    class="text-muted-foreground hover:text-foreground text-xs transition-colors"
                                    @click="cancelEdit"
                                >
                                    Batal
                                </button>
                            </div>
                        </div>

                        <!-- Display mode -->
                        <template v-else>
                            <p class="mt-0.5 text-sm whitespace-pre-line">{{ comment.body }}</p>
                            <div v-if="currentUser" class="flex items-center gap-3 mt-1">
                                <!-- Reply button -->
                                <button
                                    class="text-muted-foreground hover:text-foreground text-xs transition-colors"
                                    @click="startReply(comment.id)"
                                >
                                    Balas
                                </button>

                                <!-- Own comment actions -->
                                <template v-if="currentUser.id === comment.user.id">
                                    <button
                                        class="text-muted-foreground hover:text-foreground text-xs transition-colors"
                                        @click="startEdit(comment)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        v-if="confirmDeleteId !== comment.id"
                                        class="text-muted-foreground hover:text-destructive text-xs transition-colors"
                                        @click="toggleDelete(comment.id)"
                                    >
                                        Hapus
                                    </button>
                                    <template v-else>
                                        <span class="text-destructive text-xs">Yakin?</span>
                                        <button
                                            class="text-destructive hover:text-destructive/80 text-xs font-medium transition-colors"
                                            @click="deleteComment(comment)"
                                        >
                                            Ya
                                        </button>
                                        <button
                                            class="text-muted-foreground hover:text-foreground text-xs transition-colors"
                                            @click="toggleDelete(comment.id)"
                                        >
                                            Tidak
                                        </button>
                                    </template>
                                </template>

                                <!-- Admin delete (not own comment) -->
                                <template v-else-if="isAdmin">
                                    <button
                                        v-if="confirmDeleteId !== comment.id"
                                        class="text-muted-foreground hover:text-destructive text-xs transition-colors"
                                        @click="toggleDelete(comment.id)"
                                    >
                                        Hapus
                                    </button>
                                    <template v-else>
                                        <span class="text-destructive text-xs">Yakin?</span>
                                        <button
                                            class="text-destructive hover:text-destructive/80 text-xs font-medium transition-colors"
                                            @click="deleteComment(comment)"
                                        >
                                            Ya
                                        </button>
                                        <button
                                            class="text-muted-foreground hover:text-foreground text-xs transition-colors"
                                            @click="toggleDelete(comment.id)"
                                        >
                                            Tidak
                                        </button>
                                    </template>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Inline reply form -->
                <div v-if="replyingToId === comment.id" class="ml-11 border-l-2 border-muted pl-4 mt-3">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 flex items-center justify-center bg-muted rounded-full size-7 font-medium text-xs text-muted-foreground">
                            {{ getInitials(currentUser!.name) }}
                        </div>
                        <div class="flex-1 space-y-2">
                            <Textarea
                                v-model="replyBody"
                                placeholder="Tulis balasan..."
                                class="min-h-[60px] resize-none text-sm"
                                @keydown.meta.enter="submitReply(comment.id)"
                                @keydown.ctrl.enter="submitReply(comment.id)"
                            />
                            <div class="flex gap-2">
                                <button
                                    class="bg-primary hover:bg-primary/90 px-3 py-1 rounded-md font-medium text-primary-foreground text-xs transition-colors disabled:opacity-50"
                                    :disabled="!replyBody.trim() || isSubmittingReply"
                                    @click="submitReply(comment.id)"
                                >
                                    Balas
                                </button>
                                <button
                                    class="text-muted-foreground hover:text-foreground text-xs transition-colors"
                                    @click="cancelReply"
                                >
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Replies -->
                <div
                    v-if="comment.replies && comment.replies.length > 0"
                    class="ml-11 border-l-2 border-muted pl-4 mt-3 space-y-3"
                >
                    <div
                        v-for="reply in comment.replies"
                        :key="reply.id"
                        class="flex gap-3"
                    >
                        <div class="flex-shrink-0 flex items-center justify-center bg-muted rounded-full size-7 font-medium text-xs text-muted-foreground">
                            {{ getInitials(reply.user.name) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-sm">{{ reply.user.name }}</span>
                                <span class="text-muted-foreground text-xs">{{ relativeTime(reply.created_at) }}</span>
                            </div>

                            <!-- Editing reply -->
                            <div v-if="editingId === reply.id" class="mt-1 space-y-2">
                                <Textarea
                                    v-model="editBody"
                                    class="min-h-[60px] resize-none text-sm"
                                    @keydown.meta.enter="saveEdit(reply)"
                                    @keydown.ctrl.enter="saveEdit(reply)"
                                />
                                <div class="flex gap-2">
                                    <button
                                        class="bg-primary hover:bg-primary/90 px-3 py-1 rounded-md font-medium text-primary-foreground text-xs transition-colors disabled:opacity-50"
                                        :disabled="!editBody.trim()"
                                        @click="saveEdit(reply)"
                                    >
                                        Simpan
                                    </button>
                                    <button
                                        class="text-muted-foreground hover:text-foreground text-xs transition-colors"
                                        @click="cancelEdit"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </div>

                            <!-- Display reply -->
                            <template v-else>
                                <p class="mt-0.5 text-sm whitespace-pre-line">{{ reply.body }}</p>
                                <div v-if="currentUser" class="flex items-center gap-3 mt-1">
                                    <!-- Own reply actions -->
                                    <template v-if="currentUser.id === reply.user.id">
                                        <button
                                            class="text-muted-foreground hover:text-foreground text-xs transition-colors"
                                            @click="startEdit(reply)"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            v-if="confirmDeleteId !== reply.id"
                                            class="text-muted-foreground hover:text-destructive text-xs transition-colors"
                                            @click="toggleDelete(reply.id)"
                                        >
                                            Hapus
                                        </button>
                                        <template v-else>
                                            <span class="text-destructive text-xs">Yakin?</span>
                                            <button
                                                class="text-destructive hover:text-destructive/80 text-xs font-medium transition-colors"
                                                @click="deleteComment(reply)"
                                            >
                                                Ya
                                            </button>
                                            <button
                                                class="text-muted-foreground hover:text-foreground text-xs transition-colors"
                                                @click="toggleDelete(reply.id)"
                                            >
                                                Tidak
                                            </button>
                                        </template>
                                    </template>

                                    <!-- Admin delete reply -->
                                    <template v-else-if="isAdmin">
                                        <button
                                            v-if="confirmDeleteId !== reply.id"
                                            class="text-muted-foreground hover:text-destructive text-xs transition-colors"
                                            @click="toggleDelete(reply.id)"
                                        >
                                            Hapus
                                        </button>
                                        <template v-else>
                                            <span class="text-destructive text-xs">Yakin?</span>
                                            <button
                                                class="text-destructive hover:text-destructive/80 text-xs font-medium transition-colors"
                                                @click="deleteComment(reply)"
                                            >
                                                Ya
                                            </button>
                                            <button
                                                class="text-muted-foreground hover:text-foreground text-xs transition-colors"
                                                @click="toggleDelete(reply.id)"
                                            >
                                                Tidak
                                            </button>
                                        </template>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="py-6 text-center">
            <p class="text-muted-foreground text-sm">
                Belum ada komentar. Jadilah yang pertama!
            </p>
        </div>
    </div>
</template>
