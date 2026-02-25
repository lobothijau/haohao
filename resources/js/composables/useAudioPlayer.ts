import { ref, onUnmounted, type Ref } from 'vue';
import type { StorySentence } from '@/types';

export function useAudioPlayer(sentences: Ref<StorySentence[]> | StorySentence[]) {
    const isPlaying = ref(false);
    const currentSentenceId = ref<number | null>(null);
    const playbackSpeed = ref(1);
    let audio: HTMLAudioElement | null = null;
    let currentIndex = -1;

    const getSentences = (): StorySentence[] => {
        return Array.isArray(sentences) ? sentences : sentences.value;
    };

    function cleanup(): void {
        if (audio) {
            audio.pause();
            audio.removeEventListener('ended', onEnded);
            audio = null;
        }
    }

    function onEnded(): void {
        playNext();
    }

    function playNext(): void {
        const list = getSentences();
        let nextIndex = currentIndex + 1;

        while (nextIndex < list.length && !list[nextIndex].audio_src) {
            nextIndex++;
        }

        if (nextIndex >= list.length) {
            stop();
            return;
        }

        currentIndex = nextIndex;
        playSentence(list[nextIndex]);
    }

    function playSentence(sentence: StorySentence): void {
        cleanup();

        if (!sentence.audio_src) {
            return;
        }

        audio = new Audio(sentence.audio_src);
        audio.playbackRate = playbackSpeed.value;
        audio.addEventListener('ended', onEnded);
        audio.play();
        currentSentenceId.value = sentence.id;
    }

    function toggle(): void {
        if (isPlaying.value) {
            stop();
        } else {
            play();
        }
    }

    function play(): void {
        const list = getSentences();

        if (list.length === 0) {
            return;
        }

        isPlaying.value = true;

        if (currentIndex < 0 || currentIndex >= list.length) {
            currentIndex = -1;
            playNext();
        } else {
            playSentence(list[currentIndex]);
        }
    }

    function stop(): void {
        cleanup();
        isPlaying.value = false;
        currentSentenceId.value = null;
        currentIndex = -1;
    }

    function playFromSentence(index: number): void {
        currentIndex = index - 1;
        isPlaying.value = true;
        playNext();
    }

    function setSpeed(rate: number): void {
        playbackSpeed.value = rate;
        if (audio) {
            audio.playbackRate = rate;
        }
    }

    onUnmounted(() => {
        cleanup();
    });

    return {
        isPlaying,
        currentSentenceId,
        playbackSpeed,
        toggle,
        stop,
        playFromSentence,
        setSpeed,
    };
}
