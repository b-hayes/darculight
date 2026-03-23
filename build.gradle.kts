defaultTasks("buildPlugin")

plugins {
    id("org.jetbrains.intellij.platform") version "2.3.0"
}

repositories {
    mavenCentral()
    intellijPlatform {
        defaultRepositories()
    }
}

dependencies {
    intellijPlatform {
        intellijIdeaCommunity("2024.3")
    }
}

intellijPlatform {
    pluginConfiguration {
        ideaVersion {
            sinceBuild = "243"
            untilBuild = provider { null }
        }
    }
}

tasks.named("buildPlugin") {
    doLast {
        println("""
            |
            | Built: build/distributions/darculight.zip
            |
            | To install: Settings → Plugins → ⚙ → Install Plugin from Disk
            |
            | NOTE: The IDE caches theme/scheme selections across installs.
            | If the wrong theme or colour scheme is active after reinstalling,
            | manually switch via Settings → Appearance → Theme and
            | Settings → Editor → Colour Scheme.
            |
        """.trimMargin())
    }
}
